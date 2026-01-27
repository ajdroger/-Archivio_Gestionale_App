# 📊 GUIDA MONITORING & OBSERVABILITY MCAG
## Setup Completo Sistemi di Monitoraggio

**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Sistema**: MCAG v8.3.0+  
**Tipo**: Guida Tecnica Operations

---

## 📋 INDICE

1. [Overview Three Pillars](#1-overview-three-pillars)
2. [Metrics (Prometheus + Grafana)](#2-metrics-prometheus--grafana)
3. [Logs (Stack ELK)](#3-logs-stack-elk)
4. [Traces (Sentry + APM)](#5-traces-sentry--apm)
6. [Database Monitoring](#6-database-monitoring)
7. [Infrastructure Monitoring](#7-infrastructure-monitoring)
8. [Alerting Rules](#8-alerting-rules)
9. [SLA Monitoring](#9-sla-monitoring)
10. [Dashboards](#10-dashboards)

---

## 1. OVERVIEW THREE PILLARS

### 1.1 Observability Model

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   METRICS    │    │     LOGS     │    │    TRACES    │
│              │    │              │    │              │
│  Prometheus  │    │ Elasticsearch│    │    Sentry    │
│   Grafana    │    │   Logstash   │    │   Jaeger     │
│              │    │    Kibana    │    │              │
└──────────────┘    └──────────────┘    └──────────────┘
       ↓                    ↓                    ↓
       └────────────────────┴────────────────────┘
                            ↓
                 ┌──────────────────────┐
                 │   ALERTING ENGINE     │
                 │                       │
                 │   AlertManager        │
                 │   PagerDuty           │
                 │   Slack/Email         │
                 └──────────────────────┘
```

### 1.2 Retention Policies

| Data Type | Hot Storage | Warm Storage | Cold Storage | Total Retention |
|-----------|-------------|--------------|--------------|-----------------|
| **Metrics (raw)** | 15 giorni | 90 giorni (downsampled 5m) | 365 giorni (downsampled 1h) | 13 mesi |
| **Logs (all)** | 7 giorni | 30 giorni | 180 giorni (compressed) | 6 mesi |
| **Traces (samples)** | 7 giorni | - | - | 7 giorni |
| **APM** | 30 giorni | 90 giorni | - | 4 mesi |

---

## 2. METRICS (Prometheus + Grafana)

### 2.1 Prometheus Setup

**Installation** (Docker Compose):

```yaml
# docker-compose-monitoring.yml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus-data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.retention.time=90d'
      - '--storage.tsdb.path=/prometheus'
      - '--web.enable-lifecycle'
    restart: unless-stopped

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=<STRONG_PASSWORD>
      - GF_INSTALL_PLUGINS=grafana-piechart-panel
    volumes:
      - grafana-data:/var/lib/grafana
      - ./grafana/dashboards:/etc/grafana/provisioning/dashboards
    restart: unless-stopped

  node-exporter:
    image: prom/node-exporter:latest
    ports:
      - "9100:9100"
    restart: unless-stopped

volumes:
  prometheus-data:
  grafana-data:
```

**Configuration** (prometheus.yml):

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    cluster: 'mcag-production'
    environment: 'production'

scrape_configs:
  # Application metrics
  - job_name: 'mcag-app'
    static_configs:
      - targets: ['app1.mcag.internal:9091', 'app2.mcag.internal:9091']
    metrics_path: '/metrics'
    scrape_interval: 10s

  # Node exporter (system metrics)
  - job_name: 'node'
    static_configs:
      - targets: ['node1:9100', 'node2:9100', 'node3:9100']

  # MySQL exporter
  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql-exporter:9104']

  # Redis exporter
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']

  # Nginx exporter
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx-exporter:9113']

# Alerting configuration
alerting:
  alertmanagers:
    - static_configs:
        - targets: ['alertmanager:9093']

rule_files:
  - '/etc/prometheus/alerts/*.yml'
```

### 2.2 Application Metrics Endpoint

**PHP Prometheus Client**:

```php
<?php
// src/Controller/MetricsController.php

namespace App\Controller;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ResponseInterface;

class MetricsController
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function metrics(): ResponseInterface
    {
        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        return new Response(
            status: 200,
            body: $result,
            headers: ['Content-Type' => RenderTextFormat::MIME_TYPE]
        );
    }
}
```

**Metrics to Track**:

```php
// Application Performance
$httpRequestDuration = $registry->getOrRegisterHistogram(
    'mcag',
    'http_request_duration_seconds',
    'HTTP request duration',
    ['method', 'route', 'status']
);

// Business Metrics
$socioCreated = $registry->getOrRegisterCounter(
    'mcag',
    'socio_created_total',
    'Total number of soci created'
);

// Error Tracking
$exceptions = $registry->getOrRegisterCounter(
    'mcag',
    'exceptions_total',
    'Total number of exceptions thrown',
    ['type', 'severity']
);

// Database Queries
$dbQueries = $registry->getOrRegisterHistogram(
    'mcag',
    'db_query_duration_seconds',
    'Database query duration',
    ['query_type']
);
```

### 2.3 Grafana Dashboards

**Dashboard: Application Overview**

```json
{
  "dashboard": {
    "title": "MCAG Application Overview",
    "panels": [
      {
        "title": "Request Rate",
        "targets": [
          {
            "expr": "rate(mcag_http_requests_total[5m])"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Response Time (p95)",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, mcag_http_request_duration_seconds_bucket)"
          }
        ]
      },
      {
        "title": "Error Rate",
        "targets": [
          {
            "expr": "rate(mcag_http_requests_total{status=~\"5..\"}[5m])"
          }
        ]
      }
    ]
  }
}
```

---

## 3. LOGS (Stack ELK)

### 3.1 Elasticsearch + Logstash + Kibana Setup

**Docker Compose**:

```yaml
# docker-compose-elk.yml
services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
      - ES_JAVA_OPTS=-Xms2g -Xmx2g
    ports:
      - "9200:9200"
    volumes:
      - es-data:/usr/share/elasticsearch/data

  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    ports:
      - "5044:5044"  # Beats input
      - "9600:9600"  # Monitoring API
    volumes:
      - ./logstash/pipeline:/usr/share/logstash/pipeline
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    ports:
      - "5601:5601"
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    depends_on:
      - elasticsearch

volumes:
  es-data:
```

**Logstash Pipeline** (logstash/pipeline/mcag.conf):

```
input {
  beats {
    port => 5044
  }
}

filter {
  # Parse JSON logs
  if [message] =~ /^\{/ {
    json {
      source => "message"
    }
  }

  # Extract fields
  if [logger] {
    mutate {
      add_field => { "application" => "mcag" }
    }
  }

  # Enrich with geoip
  if [ip] {
    geoip {
      source => "ip"
      target => "geoip"
    }
  }

  # Add timestamp
  date {
    match => [ "timestamp", "ISO8601" ]
    target => "@timestamp"
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "mcag-logs-%{+YYYY.MM.dd}"
  }
}
```

### 3.2 Application Logging

**Monolog Configuration** (config/logging.php):

```php
<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\JsonFormatter;

return [
    'default' => 'stack',
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'elasticsearch'],
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => '/var/log/mcag/app.log',
            'level' => 'debug',
            'days' => 7,
        ],
        'elasticsearch' => [
            'driver' => 'custom',
            'handler' => ElasticsearchHandler::class,
            'formatter' => JsonFormatter::class,
            'level' => 'info',
        ],
    ],
];
```

**Structured Logging Example**:

```php
use Psr\Log\LoggerInterface;

class SocioService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function createSocio(array $data): Socio
    {
        $this->logger->info('Creating socio', [
            'email' => $data['email'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        try {
            $socio = $this->repository->create($data);

            $this->logger->info('Socio created successfully', [
                'socio_id' => $socio->id,
                'matricola' => $socio->matricola,
                'duration_ms' => ...,
            ]);

            return $socio;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create socio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $data,
            ]);

            throw $e;
        }
    }
}
```

### 3.3 Log Analysis Queries (Kibana)

**Top 10 Errors (last 24h)**:
```
level:error AND @timestamp:[now-24h TO now]
| top errors.count by error.type
```

**Slow Queries (> 1s)**:
```
duration_ms:>1000 AND query_type:database
| stats avg(duration_ms) by query_name
```

**Failed Login Attempts by IP**:
```
event:login_failed
| stats count() by ip
| where count > 10
```

---

## 4. TRACES (Sentry + APM)

### 4.1 Sentry Integration

**Installation**:

```bash
composer require sentry/sentry-symfony
```

**Configuration** (config/sentry.yaml):

```yaml
sentry:
    dsn: '%env(SENTRY_DSN)%'
    environment: '%env(APP_ENV)%'
    release: '%env(APP_VERSION)%'
    traces_sample_rate: 0.2  # 20% delle transazioni
    profiles_sample_rate: 0.1  # 10% profiling
    options:
        attach_stacktrace: true
        send_default_pii: false  # GDPR compliant
        max_breadcrumbs: 50
        before_send:
            - App\Sentry\BeforeSendCallback
```

**Error Context Enrichment**:

```php
<?php

namespace App\Sentry;

use Sentry\Event;
use Sentry\EventHint;

class BeforeSendCallback
{
    public function __invoke(Event $event, EventHint $hint): ?Event
    {
        // Add custom context
        $event->setUser([
            'id' => auth()->id() ?? 'guest',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
        ]);

        $event->setTags([
            'php_version' => PHP_VERSION,
            'app_version' => config('app.version'),
        ]);

        // Filter sensitive data
        if ($event->getRequest()) {
            $event->getRequest()->setData(
                $this->filterSensitiveData($event->getRequest()->getData())
            );
        }

        return $event;
    }

    private function filterSensitiveData(array $data): array
    {
        $sensitive = ['password', 'token', 'api_key', 'secret'];

        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[FILTERED]';
            }
        }

        return $data;
    }
}
```

### 4.2 Performance Monitoring

**Track Transactions**:

```php
use Sentry\Tracing\TransactionContext;

$transactionContext = new TransactionContext();
$transactionContext->setName('POST /api/soci');
$transactionContext->setOp('http.server');

$transaction = \Sentry\startTransaction($transactionContext);

// Your business logic
$socio = $socioService->create($data);

// Create spans for specific operations
$span = $transaction->startChild([
    'op' => 'db.query',
    'description' => 'INSERT into soci',
]);
// ... query execution
$span->finish();

$transaction->finish();
```

---

## 5. DATABASE MONITORING

### 5.1 MySQL Metrics

**MySQL Exporter** (Prometheus):

```yaml
# mysqld-exporter.yml
  mysql-exporter:
    image: prom/mysqld-exporter
    environment:
      - DATA_SOURCE_NAME=exporter:password@(mysql:3306)/
   ports:
      - "9104:9104"
```

**Key Metrics**:
- `mysql_global_status_connections`: Total connections
- `mysql_global_status_slow_queries`: Slow queries count
- `mysql_global_status_threads_connected`: Active threads
- `mysql_innodb_buffer_pool_pages_dirty`: Dirty pages

**Slow Query Log Analysis**:

```bash
# Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;  # 1 second threshold

# Analyze with pt-query-digest
pt-query-digest /var/log/mysql/slow.log \
  --limit 10 \
  --output json > slow-queries-report.json
```

### 5.2 Redis Monitoring

**Redis Exporter**:

```yaml
  redis-exporter:
    image: oliver006/redis_exporter
    environment:
      - REDIS_ADDR=redis:6379
      - REDIS_PASSWORD=<password>
    ports:
      - "9121:9121"
```

**Key Metrics**:
- `redis_connected_clients`: Active connections
- `redis_memory_used_bytes`: Memory usage
- `redis_keyspace_hits_total`: Cache hit rate
- `redis_evicted_keys_total`: Evictions

---

## 6. INFRASTRUCTURE MONITORING

### 6.1 Node Exporter (System Metrics)

**Metrics Tracked**:
- CPU usage, load average
- Memory usage (free, cached, buffers)
- Disk I/O, usage, inodes
- Network traffic

**Alert Example**:

```yaml
# alerts/infrastructure.yml
groups:
  - name: infrastructure
    rules:
      - alert: HighCPUUsage
        expr: 100 - (avg by (instance) (rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 80
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High CPU usage on {{ $labels.instance }}"
          description: "CPU usage is {{ $value }}%"

      - alert: DiskSpaceLow
        expr: (node_filesystem_avail_bytes / node_filesystem_size_bytes) * 100 < 10
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Disk space low on {{ $labels.instance }}"
```

---

## 7. ALERTING RULES

### 7.1 AlertManager Configuration

```yaml
# alertmanager.yml
global:
  resolve_timeout: 5m
  slack_api_url: '<SLACK_WEBHOOK>'

route:
  receiver: 'default'
  group_by: ['alertname', 'cluster', 'service']
  group_wait: 10s
  group_interval: 5m
  repeat_interval: 3h
  routes:
    - match:
        severity: critical
      receiver: pagerduty
      continue: true
    - match:
        severity: warning
      receiver: slack

receivers:
  - name: 'default'
    email_configs:
      - to: 'ops@mcag.it'

  - name: 'slack'
    slack_configs:
      - channel: '#alerts'
        title: '{{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.description }}{{ end }}'

  - name: 'pagerduty'
    pagerduty_configs:
      - service_key: '<PAGERDUTY_KEY>'
        description: '{{ .GroupLabels.alertname }}'
```

### 7.2 Application Alert Rules

```yaml
# alerts/application.yml
groups:
  - name: application
    rules:
      - alert: HighErrorRate
        expr: rate(mcag_http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "High error rate (>5%)"

      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, mcag_http_request_duration_seconds_bucket) > 2
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "95th percentile response time > 2s"

      - alert: DatabaseConnectionPoolExhausted
        expr: mcag_db_connections_active / mcag_db_connections_max > 0.9
        for: 2m
        labels:
          severity: critical
```

---

## 8. SLA MONITORING

### 8.1 Uptime Tracking

**UptimeRobot Integration**:
- Monitors: https://app.mcag.it/health every 5 min
- SMS alert se down > 2 minuti
- Status page: https://status.mcag.it

**SLA Target**: 99.9% uptime (max 43 minuti downtime/mese)

### 8.2 SLI/SLO Definitions

| Service | SLI (Service Level Indicator) | SLO (Service Level Objective) |
|---------|-------------------------------|-------------------------------|
| **API Availability** | % of successful requests (2xx/3xx) | ≥ 99.9% |
| **API Latency** | 95th percentile response time | ≤ 500ms |
| **Database Availability** | % uptime | ≥ 99.95% |
| **Page Load Time** | Time to interactive | ≤ 3 secondi |

---

## 9. DASHBOARDS

### 9.1 Executive Dashboard

**Panels**:
- Total users (trend 7d/30d)
- Revenue (daily/monthly)
- Active sessions
- New customers this month
- System health score (composite)

### 9.2 Operations Dashboard

**Panels**:
- Request rate (req/s)
- Error rate (%)
- Response time (p50, p95, p99)
- Database query time
- Cache hit rate
- Queue depth

### 9.3 Security Dashboard

**Panels**:
- Failed login attempts
- Suspicious IP activity
- OWASP Top 10 attack attempts
- API rate limit violations
- File upload malware detections

---

## CONCLUSIONE

Un sistema di monitoring completo è **essenziale per operational excellence**. MCAG implementa best practices industry-standard:

- 📊 **Metrics**: Prometheus + Grafana (real-time KPIs)
- 📝 **Logs**: ELK Stack (centralized, searchable)
- 🔍 **Traces**: Sentry (error tracking, performance)
- 🚨 **Alerts**: Multi-channel (Slack, PagerDuty, Email)
- 📈 **SLA**: 99.9% uptime guarantee

**Continuous Improvement**: Review dashboards/alerts quarterly, adapt to new requirements.

---

**© 2026 Soobadur Mohammad Ajmeer - All Rights Reserved**  
**MCAG Monitoring Guide**  
**Versione**: 1.0  
**Data**: 27 Gennaio 2026
