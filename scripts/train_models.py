#!/usr/bin/env python3
"""Train lightweight fraud and FX volatility models from CSV datasets.

The script expects CSV files in ``storage/app/data`` with the following columns:

``fraud_training.csv``
    transaction_amount, chargeback_ratio, customer_risk_score, velocity, label

``fx_training.csv``
    recent_volatility, liquidity_index, macro_risk_score, target
"""

from __future__ import annotations

import csv
import json
import math
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List

ROOT = Path(__file__).resolve().parents[1]
DATA_DIR = ROOT / "storage" / "app" / "data"
MODEL_DIR = ROOT / "storage" / "app" / "models"
MODEL_DIR.mkdir(parents=True, exist_ok=True)


@dataclass
class LogisticRegressionModel:
    weights: Dict[str, float]
    bias: float

    def predict(self, row: Dict[str, float]) -> float:
        score = self.bias
        for feature, weight in self.weights.items():
            score += weight * row.get(feature, 0.0)
        return 1.0 / (1.0 + math.exp(-score))


@dataclass
class LinearModel:
    weights: Dict[str, float]
    bias: float

    def predict(self, row: Dict[str, float]) -> float:
        score = self.bias
        for feature, weight in self.weights.items():
            score += weight * row.get(feature, 0.0)
        return score


def load_csv(path: Path) -> List[Dict[str, float]]:
    if not path.exists():
        return []

    with path.open() as fh:
        reader = csv.DictReader(fh)
        rows: List[Dict[str, float]] = []
        for row in reader:
            parsed = {key: float(value) for key, value in row.items() if value not in (None, "")}
            rows.append(parsed)
        return rows


def train_logistic(rows: List[Dict[str, float]], label_key: str, epochs: int = 200, lr: float = 0.01) -> LogisticRegressionModel:
    if not rows:
        raise ValueError("No rows provided for logistic regression training")

    features = [key for key in rows[0] if key != label_key]
    weights = {feature: 0.0 for feature in features}
    bias = 0.0

    for _ in range(epochs):
        for row in rows:
            label = row[label_key]
            inputs = {feature: row[feature] for feature in features}
            model = LogisticRegressionModel(weights, bias)
            pred = model.predict(inputs)
            error = pred - label
            for feature in features:
                weights[feature] -= lr * error * inputs[feature]
            bias -= lr * error

    return LogisticRegressionModel(weights, bias)


def train_linear(rows: List[Dict[str, float]], target_key: str, epochs: int = 200, lr: float = 0.01) -> LinearModel:
    if not rows:
        raise ValueError("No rows provided for linear regression training")

    features = [key for key in rows[0] if key != target_key]
    weights = {feature: 0.0 for feature in features}
    bias = 0.0

    for _ in range(epochs):
        for row in rows:
            target = row[target_key]
            inputs = {feature: row[feature] for feature in features}
            model = LinearModel(weights, bias)
            pred = model.predict(inputs)
            error = pred - target
            for feature in features:
                weights[feature] -= lr * error * inputs[feature]
            bias -= lr * error

    return LinearModel(weights, bias)


def main() -> None:
    fraud_rows = load_csv(DATA_DIR / "fraud_training.csv")
    fx_rows = load_csv(DATA_DIR / "fx_training.csv")

    if fraud_rows:
        fraud_model = train_logistic(fraud_rows, label_key="label")
        (MODEL_DIR / "fraud_model.json").write_text(
            json.dumps({"weights": fraud_model.weights, "bias": fraud_model.bias}, indent=4)
        )
        print("Updated fraud_model.json")
    else:
        print("No fraud training data found; skipping fraud model update")

    if fx_rows:
        fx_model = train_linear(fx_rows, target_key="target")
        (MODEL_DIR / "fx_volatility_model.json").write_text(
            json.dumps({"weights": fx_model.weights, "bias": fx_model.bias}, indent=4)
        )
        print("Updated fx_volatility_model.json")
    else:
        print("No FX training data found; skipping fx model update")


if __name__ == "__main__":
    main()
