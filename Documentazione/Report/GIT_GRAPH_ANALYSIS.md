```mermaid
gitGraph
    commit id: "v1.0.0" tag: "v1.0.0"
    branch develop
    checkout develop
    commit id: "init-dev"
    
    %% PHASE 1: CORE FOUNDATION (v2.x)
    branch feature/separation-of-concerns
    commit id: "clean-arch"
    checkout develop
    merge feature/separation-of-concerns
    
    branch feature/code-quality-upgrade
    commit id: "phpstan-l6"
    checkout develop
    merge feature/code-quality-upgrade

    branch release/v2.0.0
    commit id: "rel-v2-prep"
    checkout main
    merge release/v2.0.0 tag: "v2.0.0-Enterprise"
    checkout develop

    %% PHASE 2: SECURITY & COMPLIANCE (v3.x - v4.x)
    branch feature/compliance-gdpr
    commit id: "gdpr-native"
    checkout develop
    merge feature/compliance-gdpr
    
    branch feature/sec-api-hardening
    commit id: "security-layer"
    checkout develop
    merge feature/sec-api-hardening

    branch feature/devtools-ultimate-v4
    commit id: "devtools-upgrade"
    checkout develop
    merge feature/devtools-ultimate-v4
    
    branch support/v4.x
    commit id: "eol-prep"
    checkout main
    merge support/v4.x tag: "v4.0-Ultimate"
    
    %% PHASE 3: AI REVOLUTION (v5.0 - v5.1)
    checkout develop
    branch feature/ai-integration-rag
    commit id: "rag-engine"
    checkout develop
    merge feature/ai-integration-rag
    
    branch feature/ai-omni-reader
    commit id: "omni-reader"
    checkout develop
    merge feature/ai-omni-reader

    branch release/v5.0.0-rc1
    commit id: "ai-release"
    checkout main
    merge release/v5.0.0-rc1 tag: "v5.0.0-AI"
    
    %% HOTFIX AI
    branch hotfix/v5.1.1-ai-assistant-fix
    commit id: "fix-ai-tokens"
    checkout main
    merge hotfix/v5.1.1-ai-assistant-fix tag: "v5.1.1"
    checkout develop
    merge hotfix/v5.1.1-ai-assistant-fix

    %% PHASE 4: REBRANDING & DEEP CLEAN (v5.3)
    checkout develop
    branch feature/rebranding-mcag
    commit id: "rebrand-start"
    checkout develop
    merge feature/rebranding-mcag
    
    branch feature/legacy-path-cleanup
    commit id: "deep-clean"
    checkout develop
    merge feature/legacy-path-cleanup
    
    branch feature/landing-refactor
    commit id: "landing-v2"
    checkout develop
    merge feature/landing-refactor

    branch feature/commercial-landing-page
    commit id: "comm-report"
    checkout develop
    merge feature/commercial-landing-page
    
    %% RECENT FIXES & STABILIZATION
    branch feature/fix-toolkit-console-and-tests
    commit id: "fix-console-base"
    checkout develop
    merge feature/fix-toolkit-console-and-tests
    
    branch hotfix/system-console-toolkit
    commit id: "fix-json-buffer"
    checkout develop
    merge hotfix/system-console-toolkit
    
    %% RELEASE v5.3.0
    branch release/stable
    commit id: "prep-v5.3"
    checkout main
    merge release/stable tag: "v5.3.0-MCAG"
    
    %% CURRENT STATE
    checkout develop
    commit id: "HEAD" type: HIGHLIGHT
```

## Analisi Struttura Branch (95 Totali)

Il grafico sopra rappresenta il **flusso logico principale** estratto dai 95 branch presenti nel repository.

### 🌳 Organizzazione Gerarchica
*   **`main`**: Production-ready code (Tag: `v2.0.0`, `v4.0`, `v5.0`, `v5.3.0`).
*   **`stable`**: Pre-production staging area (usato per stabilizzazione `v5.3`).
*   **`develop`**: Development trunk, riceve tutti i merge delle feature.
*   **`support/v4.x`**: Branch di manutenzione Long Term Support per la versione legacy.

### 🚀 Categorie Branch Attivi
1.  **AI & Innovation**: `feature/ai-integration-rag`, `feature/ai-omni-reader`, `feature/v5.1-advanced-ai`.
2.  **Core Rebranding**: `feature/rebranding-mcag`, `feature/rebranding-mcag-v5.3`, `feature/legacy-path-cleanup`.
3.  **Commercial & Assets**: `feature/commercial-landing-page`, `feature/landing-ui-update`, `feature/legal-kit-finalization`.
4.  **DevOps & Tools**: `feature/devtools-ultimate-v4`, `feature/devops-pipeline`, `hotfix/system-console-toolkit`.
5.  **Security**: `feature/sec-api-hardening`, `feature/compliance-gdpr`, `feature/db-encryption`.
