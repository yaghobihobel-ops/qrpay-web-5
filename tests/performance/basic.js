import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

const vus = parseInt(__ENV.VUS || '10', 10);
const duration = __ENV.DURATION || '30s';
const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8000';

export const options = {
  vus,
  duration,
  thresholds: {
    http_req_duration: ['p(95)<800'],
    http_req_failed: ['rate<0.01'],
  },
};

const latencyTrend = new Trend('request_latency');

export default function () {
  const response = http.get(`${baseUrl}/`);
  latencyTrend.add(response.timings.duration);

  check(response, {
    'response status is acceptable': (res) => res.status === 200 || res.status === 302,
  });

  sleep(1);
}
