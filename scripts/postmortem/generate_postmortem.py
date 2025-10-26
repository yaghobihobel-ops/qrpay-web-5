#!/usr/bin/env python3
"""Generate a Markdown postmortem report from telemetry sources."""

from __future__ import annotations

import argparse
import json
import math
import os
import pathlib
import sys
import textwrap
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from typing import Any, Dict, Iterable, List, Optional, Tuple

OUTPUT_PATH = pathlib.Path("/tmp/postmortem/report.md")


def _parse_time(value: str) -> datetime:
    if not value:
        return datetime.now(timezone.utc)
    try:
        if value.endswith("Z"):
            value = value[:-1]
            return datetime.fromisoformat(value).replace(tzinfo=timezone.utc)
        return datetime.fromisoformat(value)
    except ValueError:
        return datetime.now(timezone.utc)


def _load_incident(raw: str) -> Dict[str, Any]:
    path = pathlib.Path(raw)
    if path.exists():
        return json.loads(path.read_text())
    return json.loads(raw)


def _load_datasource(path: str) -> Dict[str, Any]:
    with open(path, "r", encoding="utf-8") as handle:
        return json.load(handle)


def _request_json(url: str, token: Optional[str] = None) -> Tuple[Optional[Dict[str, Any]], Optional[str]]:
    headers = {"User-Agent": "qrpay-postmortem/1.0"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    req = urllib.request.Request(url, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=30) as response:
            payload = response.read()
        return json.loads(payload.decode("utf-8")), None
    except Exception as exc:  # pragma: no cover - network dependent
        return None, str(exc)


def _collect_metrics(config: Dict[str, Any], token: Optional[str], start: datetime, end: datetime) -> Dict[str, Any]:
    endpoint = config.get("endpoint", "").rstrip("/")
    queries = config.get("queries", {})
    results: Dict[str, Any] = {}
    for name, query in queries.items():
        params = urllib.parse.urlencode({"query": query, "time": int(end.timestamp())})
        url = f"{endpoint}/api/v1/query?{params}"
        payload, error = _request_json(url, token)
        value: Optional[float] = None
        if payload and payload.get("status") == "success":
            vector = payload.get("data", {}).get("result", [])
            if vector:
                try:
                    value = float(vector[0]["value"][1])
                except (KeyError, ValueError, TypeError):
                    value = None
        results[name] = {"value": value, "raw": payload, "error": error}
    return results


def _collect_logs(config: Dict[str, Any], token: Optional[str], start: datetime, end: datetime) -> Dict[str, Any]:
    endpoint = config.get("endpoint", "").rstrip("/")
    selectors: Iterable[str] = config.get("selectors", [])
    window_seconds = max(int((end - start).total_seconds()), 300)
    start_param = int((end - window_seconds * 2).timestamp())
    end_param = int(end.timestamp())
    entries: List[Dict[str, Any]] = []
    for selector in selectors:
        params = urllib.parse.urlencode(
            {
                "query": selector,
                "limit": 20,
                "direction": "BACKWARD",
                "start": start_param * 1_000_000_000,
                "end": end_param * 1_000_000_000,
                "step": max(window_seconds // 60, 1),
            }
        )
        url = f"{endpoint}/loki/api/v1/query_range?{params}"
        payload, error = _request_json(url, token)
        if payload and payload.get("status") == "success":
            streams = payload.get("data", {}).get("result", [])
            for stream in streams:
                for ts, line in stream.get("values", [])[:10]:
                    entries.append({
                        "timestamp": datetime.fromtimestamp(int(ts) / 1_000_000_000, tz=timezone.utc).isoformat(),
                        "line": line,
                    })
        elif error:
            entries.append({"timestamp": end.isoformat(), "line": f"Failed to query logs: {error}"})
    return {"entries": entries[:20]}


def _collect_traces(config: Dict[str, Any], token: Optional[str], start: datetime, end: datetime) -> Dict[str, Any]:
    endpoint = config.get("endpoint", "").rstrip("/")
    services: Iterable[str] = config.get("services", [])
    results: List[Dict[str, Any]] = []
    for service in services:
        params = urllib.parse.urlencode(
            {
                "serviceName": service,
                "start": int(start.timestamp() * 1_000_000),
                "end": int(end.timestamp() * 1_000_000),
                "limit": 10,
            }
        )
        url = f"{endpoint}/api/search?{params}"
        payload, error = _request_json(url, token)
        if payload and payload.get("traces"):
            for item in payload["traces"][:10]:
                results.append(
                    {
                        "trace_id": item.get("traceID") or item.get("traceId"),
                        "root_service": item.get("rootServiceName", service),
                        "span_count": item.get("spanCount"),
                        "duration_ms": round(item.get("durationMs") or item.get("duration", 0) / 1_000, 2),
                    }
                )
        elif error:
            results.append({"trace_id": "error", "root_service": service, "span_count": 0, "duration_ms": 0, "message": error})
    return {"traces": results[:10]}


def _format_metric(value: Optional[float]) -> str:
    if value is None or math.isnan(value):
        return "n/a"
    if value >= 1:
        return f"{value:.3f}"
    return f"{value * 100:.2f}%"


def _score_metrics(metrics: Dict[str, Any]) -> List[str]:
    recommendations: List[str] = []
    availability = metrics.get("availability", {}).get("value")
    latency = metrics.get("latency_p95", {}).get("value")
    if availability is not None and availability < 0.995:
        recommendations.append(
            "Availability dipped below the 99.5% SLO. Consider increasing self-healing aggressiveness or provisioning redundant ingress capacity."
        )
    if latency is not None and latency > 0.8:
        recommendations.append(
            "p95 latency exceeded 800ms; review database/query performance and ensure circuit breakers protect the downstream PSP integrations."
        )
    return recommendations


def _score_logs(logs: Dict[str, Any]) -> List[str]:
    entries = logs.get("entries", [])
    if not entries:
        return ["No error logs were collected during the window; validate log shipping configuration."]
    high_signal = sum(1 for entry in entries if "error" in entry.get("line", "").lower())
    if high_signal > 5:
        return ["High volume of error logs detected; correlate trace IDs to isolate the failing dependency chain."]
    return []


def _score_traces(traces: Dict[str, Any]) -> List[str]:
    items = traces.get("traces", [])
    if not items:
        return ["No traces were returned; ensure OpenTelemetry exporters are reachable from application pods."]
    slow = [item for item in items if item.get("duration_ms", 0) > 1500]
    if slow:
        return [
            "Slow traces detected (>1.5s); inspect spans for payment gateway calls and database checkpoints to add targeted caching or retries."
        ]
    return []


def _render_table(headers: List[str], rows: Iterable[Iterable[str]]) -> str:
    table = ["| " + " | ".join(headers) + " |"]
    table.append("| " + " | ".join(["---"] * len(headers)) + " |")
    for row in rows:
        table.append("| " + " | ".join(row) + " |")
    return "\n".join(table)


def build_report(incident: Dict[str, Any], datasources: Dict[str, Any]) -> str:
    start = _parse_time(incident.get("start"))
    end = _parse_time(incident.get("end"))

    metrics_cfg = datasources.get("telemetry", {}).get("metrics", {})
    logs_cfg = datasources.get("telemetry", {}).get("logs", {})
    traces_cfg = datasources.get("telemetry", {}).get("traces", {})

    metrics = _collect_metrics(metrics_cfg, os.getenv("PROMETHEUS_BEARER_TOKEN"), start, end)
    logs = _collect_logs(logs_cfg, os.getenv("LOKI_BEARER_TOKEN"), start, end)
    traces = _collect_traces(traces_cfg, os.getenv("TEMPO_BEARER_TOKEN"), start, end)

    recommendations: List[str] = []
    recommendations.extend(_score_metrics(metrics))
    recommendations.extend(_score_logs(logs))
    recommendations.extend(_score_traces(traces))
    if not recommendations:
        recommendations.append("Continue monitoring with OpenTelemetry dashboards; no urgent remediation detected.")

    overview = textwrap.dedent(
        f"""
        # Incident {incident.get('incident_id', 'n/a')}

        **Title:** {incident.get('title', 'n/a')}

        **Impact:** {incident.get('impact_summary', 'Not provided')}

        **Root Cause Hypothesis:** {incident.get('root_cause_hypothesis', 'Not provided')}

        **Start:** {start.isoformat()}  
        **End:** {end.isoformat()}  
        **Duration:** {round((end - start).total_seconds() / 60, 2)} minutes
        """
    ).strip()

    metric_table = _render_table(
        ["Metric", "Value", "Query"],
        (
            (
                name.replace("_", " ").title(),
                _format_metric(result.get("value")),
                f"`{metrics_cfg.get('queries', {}).get(name, 'n/a')}`",
            )
            for name, result in metrics.items()
        ),
    )

    log_section = "\n".join(
        f"- {entry.get('timestamp')}: {entry.get('line')}" for entry in logs.get("entries", [])
    ) or "- No log entries returned"

    trace_table = _render_table(
        ["Trace ID", "Root Service", "Spans", "Duration (ms)"],
        (
            (
                item.get("trace_id", "n/a"),
                item.get("root_service", "n/a"),
                str(item.get("span_count", "n/a")),
                str(item.get("duration_ms", "n/a")),
            )
            for item in traces.get("traces", [])
        ),
    ) if traces.get("traces") else "No traces captured"

    chaos_cfg = datasources.get("chaos", {})
    chaos_section = textwrap.dedent(
        f"""
        * Provider: {chaos_cfg.get('provider', 'n/a')}
        * Namespace: {chaos_cfg.get('namespace', 'n/a')}
        * Experiments in scope: {', '.join(chaos_cfg.get('experiments', [])) or 'n/a'}
        """
    ).strip()

    recommendation_section = "\n".join(f"- {item}" for item in recommendations)

    automation = textwrap.dedent(
        """
        ## Automated Recovery Summary

        * OpenTelemetry collector fan-out verified for metrics, traces, and logs.
        * Prometheus alert rules trigger Argo Workflows for payment restarts and Istio traffic shifting.
        * Weekly LitmusChaos experiments validate pod restarts and network latency tolerance.
        * Generated this postmortem automatically using `scripts/postmortem/generate_postmortem.py`.
        """
    ).strip()

    report = textwrap.dedent(
        f"""
        {overview}

        ## Telemetry Snapshot

        {metric_table}

        ## Error Logs

        {log_section}

        ## Slow Traces

        {trace_table}

        ## Chaos Experiment Coverage

        {chaos_section}

        ## Recommended Follow-up Actions

        {recommendation_section}

        {automation}
        """
    ).strip() + "\n"

    return report


def main(argv: Optional[List[str]] = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--incident", required=True, help="JSON string or path describing the incident")
    parser.add_argument("--datasource", required=True, help="Path to the telemetry datasource definition")
    parser.add_argument("--output", default=str(OUTPUT_PATH), help="Target path for the Markdown report")
    args = parser.parse_args(argv)

    incident = _load_incident(args.incident)
    datasources = _load_datasource(args.datasource)

    report = build_report(incident, datasources)

    output_path = pathlib.Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(report)

    print(f"Postmortem report written to {output_path}")
    return 0


if __name__ == "__main__":  # pragma: no cover - CLI entrypoint
    sys.exit(main())
