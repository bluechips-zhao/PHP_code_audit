<div align="center">

# 🛡️ PHP\_code\_audit\_skills

**Full-Chain PHP Code Security Audit AI Agent System**

**Author: bluechips**

![Version](https://img.shields.io/badge/version-V1.0-blue)
![Skills](https://img.shields.io/badge/skills-145+-green)
![Skill Files](https://img.shields.io/badge/skill_files-121-brightgreen)
![Auditors](https://img.shields.io/badge/auditors-21_types_×_2_stages-red)
![Schemas](https://img.shields.io/badge/schemas-31-orange)
![Phase](https://img.shields.io/badge/phases-6-purple)
![Controllability](https://img.shields.io/badge/controllability-560+_constraints-yellow)

**English** | [中文文档](./README_CN.md)

A multi-agent collaborative security audit framework based on Claude Code Agent Teams. 40+ specialized agents work in concert, covering the full chain from environment setup → static reconnaissance → dynamic tracing → deep adversarial exploitation → post-exploitation correlation → report generation. Supports **21 vulnerability types** expert-level auditing with **PHP 8.x** full-version security coverage.

[Features](#features) · [Quick Start](#quick-start) · [Architecture](#architecture) · [Agent Roster](#agent-roster) · [Output Artifacts](#output-artifacts)

</div>

---

## Features

### 🔄 Full-Chain Automated Pipeline

6 strictly sequential phases, each enforced by Gate checkpoints — no skipping, no omission:

```
Phase 1 Environment → Phase 2 Static Recon → Phase 3 Dynamic Tracing
→ Phase 4 Deep Adversarial → Phase 4.5 Post-Exploitation → Phase 5 Reporting
```

- **Resume from Checkpoint**: `checkpoint.json` records phase state; resume after interruption
- **Incremental Audit**: Git diff detects changes; offers incremental mode when <10 files changed
- **Self-Recovery**: Auto-recovery for 5 failure scenarios (DB corruption, agent crash, token overflow, disk full, etc.)
- **Config-Driven**: `audit_config.yaml` customizes timeouts, rounds, priority thresholds, exclusion paths, and all parameters

### 🎯 21 Vulnerability Types + PHP 8.x Full Coverage

| Category | Vulnerability Types |
|----------|-------------------|
| **Injection** | RCE (command/code execution), SQLi (1st + 2nd order), NoSQL (MongoDB/Redis), XXE, LDAP, CRLF |
| **File** | LFI (local/remote file inclusion), FileWrite (upload/write) |
| **Web** | XSS, SSTI, SSRF (+DNS Rebinding), CSRF |
| **Logic** | Authorization Bypass/IDOR, Business Logic Flaws, Race Conditions (incl. Fiber concurrency) |
| **Data** | Deserialization (+POP chain), Session Management Flaws |
| **Ops** | Configuration Flaws, Weak Crypto/Key Leakage, Information Disclosure, Log Injection |
| **Framework** | WordPress-specific Vulnerabilities |

**PHP 8.x Attack Surface**:

| Feature | Min Version | Security Risk |
|---------|-------------|---------------|
| Named Arguments Injection | 8.0 | Override security default params to bypass XSS protection |
| First-Class Callable Syntax | 8.1 | Bypass string callback checks for RCE |
| Fiber Concurrency | 8.1 | TOCTOU race conditions |
| Enum::from() Type Confusion | 8.1 | Type confusion and information leakage |
| Attribute Injection | 8.0 | Dynamic properties bypass auth |
| Match Expression Coercion | 8.0 | Type coercion bypasses strict comparison |

### 🧠 Intelligent Attack Memory System

Dual-layer memory architecture with cross-project experience accumulation:

- **Flat Memory**: `attack_memory` table — match historical attack experience by sink_type + framework + PHP version + WAF fingerprint
- **Relational Graph Memory**: `memory_nodes` + `memory_edges` tables — 7 entity-relation types, support automatic attack chain discovery
- **Cross-Project Learning**: Historical audit experience auto-accumulates; new projects auto-match similar patterns

### 🔬 Mini-Researcher

5 auto-trigger conditions (unknown components, Critical CVE without PoC, 5+ consecutive attack failures, etc.). Local knowledge base → NVD/GitHub Advisory external intelligence → structured output with 3-level confidence consumption.

### ⚡ Hybrid Scheduling + Adversarial Loop

- **Parallel Analysis + Serial Attack**: 21 experts first analyze in parallel, then attack one-by-one with exclusive container access
- **Configurable Attack Rounds**: Default 8 rounds, adjustable via config (1-20), with early-stop mechanism
- **Pivot When Stuck**: Auto-pivot on consecutive failures (SQLi→2nd-order SQLi, XSS→SSTI, LFI→php://filter RCE, etc.)
- **Framework-Aware Scheduling**: Laravel / ThinkPHP / Symfony / WordPress feature detection and mandatory audit items
- **Version-Aware Scheduling**: PHP < 8.0 → Type Juggling, ThinkPHP 5.x → RCE, PHP 8.1+ → Fiber/Enum, etc.

### 👤 Human-in-the-Loop

6 key decision points, disabled by default. When enabled, 60-second timeout applies defaults. Decisions are cumulative (one "no" = subsequent similar ones auto-skip):

| Decision Point | Phase | Trigger | Default |
|---------------|-------|---------|---------|
| DP-1 Destructive Test | Phase-4 | Attack may modify/delete data | Reject |
| DP-2 Manual Credentials | Phase-3 | Auto-login failed | Degrade and continue |
| DP-3 Low Confidence Deep Dive | Phase-4 | Confidence < 50% | Skip |
| DP-4 Scope Adjustment | Phase-2 | Sinks > 50 | Keep current threshold |
| DP-5 Framework Mismatch | Phase-1 | Detected differs from expected | Use detected framework |
| DP-6 Critical Confirmation | Phase-4 | First Critical vulnerability | Continue auditing |

### 🔒 Quality Assurance System

- **Gate Checkpoints**: Mandatory artifact validation at each phase end (GATE-1 through GATE-4.5)
- **Independent QC Pool**: On-demand quality checkers spawned per completion
- **31 JSON Schemas**: Strict format validation for all inter-agent data exchange, 251 string fields constrained
- **Sink Registry**: `sink_registry.json` as single source of truth, loaded at runtime by `sink_finder.php`
- **Schema-Doc Consistency Check**: `validate_shared.php` auto-detects inconsistencies between `data_contracts.md` and `schemas/`
- **560+ Controllability Constraints**: Fill-in templates + anti-hallucination rules + Schema validation, eliminating free-text hallucination risk

### ✅ Automated Patch Verification

Phase-4.5 generates patches and auto-executes 7-step verification: Apply → Verify application → Replay attack payload → Check fix → Check regression → Rollback patch → Verify rollback. Results categorized: `verified` / `partial_regression` / `not_fixed` / `apply_failed` / `rollback_failed`

### 📊 Professional Audit Report

Single-file comprehensive report with: Embedded Context Packs, Burp reproduction templates, Mermaid attack chain visualization, CVSS progress bars (`████████░░ 9.45/10`), SARIF 2.1.0 export, Patch verification status.

---

## Architecture

### Phase Overview

| Phase | Agents | Core Functions | Key Artifacts |
|-------|--------|---------------|---------------|
| **Phase 1: Environment** | 3 | Framework fingerprint, Schema reconstruction, Docker build + self-heal | `environment_status.json` |
| **Phase 2: Static Recon** | 12 | Tool scanning (7 types), Route mapping, Auth matrix, Dependency scanning, Context extraction, Risk classification | `priority_queue.json`, `context_packs/` |
| **Phase 3: Dynamic Tracing** | 3+N | Auth simulation, Xdebug tracing, Call chain verification | `traces/*.json`, `credentials.json` |
| **Phase 4: Deep Exploitation** | 21+1 | 21 vulnerability type expert audit + Mini-Researcher | `exploits/*.json`, `research/*.json` |
| **Phase 4.5: Post-Exploitation** | 4 | Attack graph, Correlation analysis, Patch auto-verification, PoC generation | `attack_graph.json`, `PoC_scripts/*.py` |
| **Phase 5: Reporting** | 3 | Report generation, SARIF export, Environment cleanup | `report/audit_report.md`, `.sarif.json` |
| **QC: Quality Control** | 2 | Independent QC pool, full-pipeline coverage | QC records in `audit_session.db` |

### Skills System

145+ skills organized in 10 subdirectories under `skills/`, using a **2-Stage Auditor Pattern** (21 auditor types × 2 stages = 42 files) and standardized **Fill-in Template Format**.

```
skills/
├── auditors/       — 42 files (21 analyze + 21 attack) + index
├── auth/           — 9 sub-skills + index
├── correlation/    — 5 correlation rules + index
├── infrastructure/ — 4 system skills + index
├── qc/             — 6 phase QC checkers + index
├── report/         — 7 chapter writers + index
├── routes/         — 8 route sub-skills + index
├── scanners/       — 7 scanner wrappers + index
├── shared/         — 9 cross-cutting protocols + index
└── trace/          — 14 trace sub-skills + index
```

**Fill-in Template Standard**: `Identity → Input Contract → 🚨 CRITICAL Rules → Fill-in Procedure → Output Contract → ✅/❌ Examples → Error Handling`

### Design Philosophy

| Principle | Meaning |
|-----------|---------|
| Fill-in Template > Free Generation | Structured fields reduce AI hallucination |
| Positive/Negative Examples > Abstract Rules | Concrete examples anchor behavior |
| Single-Responsibility Agents > Monolith | Each Agent does one thing only |
| Independent QC, No Self-Review | Quality checks by independent QC agents |
| English Instructions, Chinese Output | Model precision + user readability |
| Single Source of Truth | `sink_registry.json` unifies Sink definitions |
| Config-Driven > Hardcoded | `audit_config.yaml` lets users customize audit parameters |

### Attack Loop

```
Query attack memory (flat + graph memory)
  ↓
Create attack plan → exploit_plan.json
  ↓
Round 1~N loop (default 8 rounds, configurable):
  ① Docker snapshot
  ② Send payload
  ③ Collect physical evidence (HTTP response / command output)
  ④ Success → write exploit + memory
  ⑤ Failure → WAF analysis → adjust strategy
  ⑥ Consecutive failures → Pivot
  ⑦ Trigger condition → Mini-Researcher delegation
  ⑧ N consecutive rounds with no new findings → Early stop
  ↓
Write attack memory → QC check → Next Sink
```

### Pivot Auto-Redirect

| Original Attack | Pivot Target |
|----------------|-------------|
| SQLi all failed | 2nd-order SQLi (store→read→concat) |
| XSS fully filtered | SSTI (`{{7*7}}` probe) |
| LFI path filtered | `php://filter` chain RCE |
| RCE disable_functions | Deserialization POP chain |
| SSRF internal unreachable | DNS Rebinding |

### Automated Patch Verification

```
E-1: git apply patch
E-2: Verify application success
E-3: Replay attack payload
E-4: Check vulnerability fixed
E-5: Check no regression
E-6: git apply -R rollback patch
E-7: Verify rollback clean
→ Write verification_status to remediation_summary.json
```

Skip conditions: Non-Git repo / Container not running / No successful payload / Config-change patches
Rollback failure emergency recovery: `git checkout -- .`

---

## Agent Roster

### Team 1 — Environment Setup (3 Agents)

| Agent | Responsibility |
|-------|---------------|
| `env_detective` | Framework fingerprint, PHP version, DB type identification |
| `schema_reconstructor` | Reconstruct DB schema from ORM models |
| `docker_builder` | Docker environment build + `env_selfheal` recovery loop |

### Team 2 — Static Reconnaissance (12 Agents)

| Agent | Responsibility |
|-------|---------------|
| `psalm_scanner` | Psalm taint analysis |
| `progpilot_scanner` | Progpilot vulnerability scan |
| `ast_scanner` | AST sink detection (loads from `sink_registry.json`) |
| `phpstan_scanner` | PHPStan static analysis |
| `semgrep_scanner` | Semgrep pattern matching |
| `composer_audit_scanner` | Composer dependency audit |
| `codeql_scanner` | CodeQL analysis (optional) |
| `route_mapper` | Route table parsing and mapping |
| `auth_auditor` | Authentication mechanism analysis |
| `dep_scanner` | Third-party component CVE detection |
| `context_extractor` | Sink context extraction + data flow analysis |
| `risk_classifier` | Risk priority classification P0/P1/P2/P3 |

### Team 3 — Dynamic Tracing (3 + N Agents)

| Agent | Responsibility |
|-------|---------------|
| `auth_simulator` | Simulate multi-role login to obtain credentials |
| `trace_dispatcher` | Read high-risk sinks, batch-create trace tasks |
| `trace_worker` ×N | Xdebug trace execution (dynamically created) |

### Team 4 — Vulnerability Audit (21 + 1 Agents)

<details>
<summary>Expand 21 Expert Auditors</summary>

| # | Agent | Coverage |
|---|-------|----------|
| 1 | `rce_auditor` | Command/code execution (incl. PHP 8.x NamedArgs/FirstClass) |
| 2 | `sqli_auditor` | SQL injection (1st + 2nd order) |
| 3 | `xss_ssti_auditor` | XSS + SSTI |
| 4 | `lfi_auditor` | Local/remote file inclusion |
| 5 | `filewrite_auditor` | File upload/write |
| 6 | `ssrf_auditor` | SSRF + DNS Rebinding |
| 7 | `xxe_auditor` | XML external entity |
| 8 | `deserial_auditor` | Deserialization + POP chain |
| 9 | `crlf_auditor` | CRLF injection |
| 10 | `csrf_auditor` | Cross-site request forgery |
| 11 | `authz_auditor` | Authorization bypass + IDOR |
| 12 | `session_auditor` | Session management flaws |
| 13 | `crypto_auditor` | Weak crypto/key leakage |
| 14 | `race_condition_auditor` | Race conditions (incl. Fiber concurrency) |
| 15 | `nosql_auditor` | MongoDB/Redis injection |
| 16 | `ldap_auditor` | LDAP injection |
| 17 | `infoleak_auditor` | Information disclosure |
| 18 | `logging_auditor` | Log injection/sensitive logging |
| 19 | `config_auditor` | Configuration flaws |
| 20 | `wordpress_auditor` | WordPress-specific vulnerabilities |
| 21 | `business_logic_auditor` | Business logic flaws |
| — | `mini_researcher` | Intelligent researcher (on-demand delegation) |

</details>

### Team 4.5 — Post-Exploitation Analysis (4 Agents)

| Agent | Responsibility |
|-------|---------------|
| `attack_graph_builder` | Build attack graph + chain exploitation paths |
| `correlation_engine` | Cross-auditor correlation + graph memory consumption + false positive elimination |
| `poc_generator` | Executable PoC script generation |
| `remediation_generator` | Fix patch generation (framework-adapted) + 7-step auto-verification |

### Team 5 — Reporting (3 Agents + 7 Chapter Writers)

| Agent | Responsibility |
|-------|---------------|
| `report_writer` | Main audit report orchestration (7 chapters parallel write → single-file assembly) |
| `sarif_exporter` | SARIF 2.1.0 standard export |
| `env_cleaner` | Xdebug cleanup + code/DB restoration |

**7 Chapter Writers**: `cover_page_writer` (cover + TOC + summary), `vuln_summary_writer` (summary table), `vuln_detail_writer` (vulnerability details), `attack_chain_writer` (combined attack chains), `coverage_stats_writer` (coverage), `risk_pool_writer` (risk pool), `lessons_writer` (lessons learned)

### QC — Independent Quality Control (2 Agents)

| Agent | Responsibility |
|-------|---------------|
| `qc_dispatcher` | QC task dispatch |
| `quality_checker` | Quality validation (incl. Mini-Researcher + graph memory specialized checks) |

---

## Quick Start

### Prerequisites

- **Docker** + **Docker Compose** (required)
- **Claude Code** (v2.1.87+)
- **tmux** (optional, split-view for parallel agents)

> This project includes complete multi-agent orchestration (phases + teams + skills), no dependency on Claude Code's experimental Agent Teams feature. When using third-party APIs, it's recommended to disable Agent Teams to avoid model compatibility issues.

### 1. Prepare Environment

```bash
docker --version
docker compose version
```

### 2. Configure Skill

Place this repository in Claude Code's skills directory, or use as project-level `.github/skills/PHP_code_audit_skills/`.

### 3. Launch Audit

```text
/php-code-audit-skills /path/to/php-project
```

The system automatically executes the 6-phase full-chain audit, producing a complete report and PoC.

### 4. Custom Configuration

Create `.php-audit.yaml` in the target project root, or use `--config` to specify a config file:

```text
/php-code-audit-skills /path/to/php-project --config /path/to/audit_config.yaml
```

```yaml
timeouts:
  phase4_per_expert: 30
attack:
  rounds: 12
  early_stop_rounds: 3
  destructive: false
priority_threshold: P1
skip_auditors:
  - wordpress
  - ldap
exclude_paths:
  - vendor/
  - tests/
human_in_loop:
  enabled: true
  ask_on_auth_failure: true
  ask_on_destructive: true
```

**Loading Priority**: CLI `--config` > Target project `.php-audit.yaml` > Built-in defaults

---

## Directory Structure

```text
PHP_code_audit_skills/
│
├── SKILL.md                          # Master orchestrator (Skill entry + config support + HITL decision points)
├── README.md                         # This file (English documentation)
├── README_CN.md                      # Chinese documentation (中文文档)
├── 全链路详细流程.md                    # Complete execution flowchart (text version)
│
├── phases/                           # Phase execution templates (7 files)
│   ├── phase1-env.md                 #   Environment detection and build
│   ├── phase2-recon.md               #   Static asset reconnaissance
│   ├── phase2-tasks-dynamic.md       #   Dynamic recon task creation
│   ├── phase3-trace.md               #   Auth simulation and dynamic tracing
│   ├── phase4-exploit.md             #   Deep adversarial audit
│   ├── phase45-post.md               #   Post-exploitation analysis (incl. Patch auto-verification)
│   └── phase5-report.md              #   Cleanup and report generation
│
├── teams/                            # Agent instruction files (40+ Agents)
│   ├── team1/                        #   Environment setup (3)
│   ├── team2/                        #   Static recon (5 dispatchers)
│   ├── team3/                        #   Dynamic tracing (3+N)
│   ├── team4/                        #   Vulnerability audit (21+1)
│   ├── team4.5/                      #   Post-exploitation (4, incl. Patch auto-verification)
│   ├── team5/                        #   Reporting (3)
│   └── qc/                           #   Quality control (2)
│
├── shared/                           # Shared knowledge base (28 files)
│   ├── anti_hallucination.md         #   Anti-hallucination rules (17 iron laws)
│   ├── php_specific_patterns.md      #   PHP-specific attack patterns (incl. PHP 8.x)
│   ├── sink_definitions.md           #   Sink function definitions (25 categories, incl. PHP 8.x)
│   ├── attack_memory.md              #   Attack memory system (flat + relational)
│   ├── attack_memory_graph.md        #   Relational graph memory model
│   ├── data_contracts.md             #   Data contracts (references schemas/)
│   ├── evidence_contract.md          #   Evidence collection standards
│   └── ...                           #   Remaining 21 shared knowledge files
│
├── schemas/                          # JSON Schema (31 files)
│   ├── sink_registry.json            #   Sink function registry (single source of truth)
│   └── ...                           #   Remaining 30 schemas
│
├── references/                       # Reference documentation (9 files)
│   ├── agent_injection_framework.md  #   Agent injection framework (L1/L2/L3)
│   └── ...                           #   Remaining 8 reference docs
│
├── tools/                            # Auxiliary tools (12 files)
│   ├── audit_db.sh                   #   DB operations (incl. dependency check + permission validation)
│   ├── sink_finder.php               #   AST Sink scanner (loads from sink_registry.json)
│   ├── trace_filter.php              #   Xdebug Trace filter
│   ├── payload_encoder.php           #   Payload encoder
│   ├── waf_detector.php              #   WAF fingerprint detection
│   ├── jwt_tester.php                #   JWT security testing
│   ├── type_juggling_tester.php      #   PHP type confusion testing
│   ├── redirect_checker.php          #   Open redirect detection
│   ├── validate_shared.php           #   shared/ validation + Schema consistency + Sink registry check
│   ├── vuln_intel.sh                 #   Vulnerability intelligence collection
│   ├── audit_monitor.sh              #   Audit monitoring
│   └── quality_report_gen.sh         #   QC report generation
│
├── templates/                        # Environment + config templates
│   ├── audit_config.yaml             #   Audit configuration template
│   ├── Dockerfile.template
│   ├── docker-compose.template.yml
│   ├── xdebug.ini.template
│   └── nginx/                        #   Nginx framework-adapted configs
│
├── assets/                           # Visual resources
├── agent-flow.mmd                    #   Agent execution flowchart (Mermaid)
└── audit-flow.mmd                    #   Audit flowchart (Mermaid)
```

---

## Tool Reference

| Tool | Purpose | Phase |
|------|---------|-------|
| `audit_db.sh` | SQLite DB operations (incl. dependency check + permission validation) | All |
| `sink_finder.php` | AST Sink scanner (loads definitions from `sink_registry.json`) | Phase-2 |
| `trace_filter.php` | Xdebug Trace simplification filter | Phase-3 |
| `payload_encoder.php` | Payload encoding (URL/Base64/Hex/double, etc.) | Phase-4 |
| `waf_detector.php` | WAF/filter fingerprint detection | Phase-4 |
| `jwt_tester.php` | JWT security testing | Phase-4 |
| `type_juggling_tester.php` | PHP type confusion loose comparison testing | Phase-4 |
| `redirect_checker.php` | Open redirect detection | Phase-4 |
| `vuln_intel.sh` | Vulnerability intelligence (NVD/GitHub Advisory) | Phase-4 |
| `audit_monitor.sh` | Real-time audit progress monitoring | All |
| `quality_report_gen.sh` | QC report summary generation | Phase-5 |
| `validate_shared.php` | shared/ integrity + Schema consistency + Sink registry validation | Dev/Maintenance |

### audit_db.sh Command Reference

```bash
# Attack Memory
bash audit_db.sh init-memory                     # Initialize (auto-includes graph memory)
bash audit_db.sh memory-write '<json>'            # Write attack experience
bash audit_db.sh memory-query '<json>'            # Query matching experience
bash audit_db.sh memory-stats                     # Memory store statistics
bash audit_db.sh memory-maintain                  # Clean expired memory

# Graph Memory
bash audit_db.sh graph-node-write '<json>'        # Write graph node
bash audit_db.sh graph-edge-write '<json>'        # Write graph edge
bash audit_db.sh graph-neighbors <node_id>        # Query neighbor nodes
bash audit_db.sh graph-by-data-object <obj>       # Query by data object
bash audit_db.sh graph-export <WORK_DIR>          # Export complete graph data

# Findings Management
bash audit_db.sh finding-write '<json>'           # Write finding
bash audit_db.sh finding-read [sink_id]           # Read findings
bash audit_db.sh finding-consume <sink_id>        # Consume finding

# Quality Control
bash audit_db.sh qc-write '<json>'                # Write QC record
bash audit_db.sh qc-read [phase]                  # Read QC records
```

---

## Output Artifacts

```
$WORK_DIR/
├── report/
│   ├── audit_report.md              ← Full Chinese single-file report
│   └── audit_report.sarif.json      ← SARIF 2.1.0
├── PoC_scripts/
│   ├── poc_{sink_id}.py             ← PoC per vulnerability
│   └── run_all.sh                   ← Batch execution
├── patches/
│   ├── {finding_id}.patch           ← Framework-adapted fix
│   └── remediation_summary.json     ← With verification_status + verification_summary
├── lessons/
│   ├── lessons_learned.md
│   └── shared_file_update_suggestions.md
├── quality_report/
│   └── quality_report.md
├── .audit_state/
│   ├── audit_config.json            ← Resolved audit configuration
│   ├── human_decisions.json         ← HITL decision records
│   └── error.log                    ← Error log
└── raw_data/                        ← Intermediate artifacts archive
    ├── exploits/, traces/, context_packs/
    ├── attack_graph.json, correlation_report.json
    └── checkpoint.json
```

### Audit Report Structure

```
audit_report.md
├── Cover (project metadata + CVSS visual progress bars)
├── Table of Contents (7-chapter anchor navigation)
├── Executive Summary (overall risk level + key findings + audit scope)
├── Vulnerability Summary Table (CVSS bars + AI verification badges + Patch status)
├── Vulnerability Details ×N
│   ├── Vulnerability Info Card (severity/type/route/sink/auth/priority)
│   ├── Context Pack (entry→call chain→sink + middleware + filters + auth bypass)
│   ├── Mermaid Attack Chain
│   ├── Data Flow Trace (Source→Sink + file:line)
│   ├── Burp Reproduction Template (Repeater + Intruder)
│   ├── Attack Iteration Log
│   └── Remediation (before vs after + verification status)
├── Combined Attack Chain Analysis (Mermaid + step table)
├── Audit Coverage Statistics
├── Unverified Risk Pool
├── Audit Lessons Learned
└── Footer (version + timestamp + tools + config summary)
```

---

## Gate Checkpoints & QC Strategy

### Gate Mandatory Validation

| Gate | Validation Conditions |
|------|----------------------|
| GATE-1 | `environment_status.json` exists |
| GATE-2 | `priority_queue.json` + `context_packs/` exist |
| GATE-3 | `credentials.json` exists |
| GATE-4 | `exploits/*.json` exist |
| GATE-4.5 | `PoC_scripts/*.py` exist |

### QC Degradation Strategy

| Phase | QC Failure Handling |
|-------|-------------------|
| Phase-1 | Return for rework (max 3 retries), self-heal loop / user intervention |
| Phase-2 | Locate responsible agent for supplement, annotate coverage and continue |
| Phase-3 | Broken-chain routes fall back to static analysis, non-blocking |
| Phase-4 | Degrade with annotation, non-blocking for report |

---

## Knowledge Injection Architecture

| Tier | Injection Timing | Content |
|------|-----------------|---------|
| **L1 (Mandatory)** | All Agent startup | `anti_hallucination.md`, `evidence_contract.md`, `data_contracts.md`, `output_standard.md` |
| **L2 (Role-based)** | Phase-4 expert startup | `sink_definitions.md` (incl. PHP 8.x), `payload_templates.md`, `attack_memory.md`, `attack_memory_graph.md`, `waf_bypass.md`, `php_specific_patterns.md` (incl. PHP 8.x), etc. — 16 files |
| **L3 (On-demand)** | Runtime trigger conditions | `lessons_learned.md`, `mini_researcher.md` |

---

## Best Practices

1. **Complete Source Code Audit** — Provide the full project source directory to minimize false negatives
2. **Preserve Docker Environment** — Facilitates reproduction verification and physical evidence collection
3. **Gate + Schema Validation** — Confirm artifact completeness before delivery
4. **Tiered Remediation** — Fix `confirmed` findings first; manually review `suspected`; reference `verification_status`
5. **Attack Memory Reuse** — Preserve `attack_memory.db` to accumulate cross-project experience
6. **Config Customization** — Create `.php-audit.yaml` per project characteristics; skip inapplicable auditors
7. **Enable Human-in-the-Loop** — For critical projects, enable `human_in_loop.enabled: true` to ensure destructive tests require confirmation

---

## Project Statistics

| Category | Count |
|----------|-------|
| Skill files (`skills/`) | 121 (111 skills + 10 indexes) |
| Vulnerability auditors (2-Stage) | 21 types × 2 = 42 files |
| Skills subdirectories | 10 |
| JSON Schema | 31 (incl. `sink_registry.json`) |
| Shared knowledge base (`shared/`) | 28 |
| Phase definitions | 7 |
| Reference documentation | 9 |
| Auxiliary tools | 12 |
| Environment templates | 11 (incl. `audit_config.yaml`) |
| Report Chapter Writers | 7 |
| Controllability constraints | 560+ |
| Total Markdown files | 210+ |

---

## Disclaimer

This project is intended solely for authorized security auditing, security research, and educational purposes. Before using this project, you must comply with the following terms:

1. **Authorization Prerequisite**: You must obtain explicit written authorization from the target system owner before conducting any security audit. Unauthorized security testing of others' systems is illegal.
2. **Lawful Use**: Users must ensure their actions comply with applicable laws and regulations in their jurisdiction, including but not limited to the Cybersecurity Law of the People's Republic of China, relevant provisions of the Criminal Law of the People's Republic of China, and other applicable local regulations.
3. **Assumption of Risk**: This tool is provided "as is" without any express or implied warranties. The author assumes no responsibility for any direct or indirect losses caused by the use of this tool (including but not limited to data loss, system damage, business interruption, legal liability, etc.).
4. **Use Restrictions**: It is strictly prohibited to use this tool for any illegal purposes, including but not limited to unauthorized intrusion, data theft, system destruction, or cyber attacks.
5. **Vulnerability Disclosure**: Security vulnerabilities discovered through this tool should be disclosed responsibly, promptly notifying the relevant vendors or system owners. Vulnerabilities must not be exploited for improper gain.
6. **Compliant Auditing**: Audit reports and PoC scripts generated by this tool are solely for verifying vulnerability existence and assisting remediation, and must not be used for other purposes.

**Using this tool indicates that you have read, understood, and agree to comply with the above terms. If you do not agree with any term, do not use this tool.**

---
