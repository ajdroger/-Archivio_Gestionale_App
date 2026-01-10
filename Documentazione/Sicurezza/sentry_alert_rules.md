# Sentry Alert Rules Definition

## Obiettivo
Definire regole precise per evitare "alert fatigue" e garantire che gli errori critici vengano notificati immediatamente.

## Regole Implementate (da configurare nella dashboard Sentry)

### 1. High Velocity Errors
*   **Condition**: Issue is seen more than **50 times** in **1 hour**.
*   **Action**: Send notification to Slack channel `#errors-critical`.
*   **Why**: Indicates a loop or widespread failure.

### 2. New Critical Issue
*   **Condition**: A new issue with level `FATAL` or `ERROR` is seen.
*   **Action**: Email to Tech Lead.
*   **Why**: Immediate awareness of new bugs after deployment.

### 3. Regression
*   **Condition**: An issue marked as "Resolved" reappears.
*   **Action**: Re-open issue and notify Assignee.
*   **Why**: Ensures recurring bugs are addressed.

### 4. Security Events
*   **Condition**: Event tag `category` equals `security`.
*   **Action**: Trigger PagerDuty (via Webhook) to Security Team.
*   **Why**: Potential attack in progress.
