---
name: php-code-audit-skills
description: >
  This skill should be used when the user asks to "审计 PHP 代码", "PHP security audit",
  "扫描 PHP 漏洞", "PHP penetration test", "代码安全审计", "run php-code-audit-skills", "php audit",
  or mentions PHP source code security analysis, vulnerability scanning, or code review
  for PHP projects. Use this skill whenever the user provides a PHP project path and
  wants security assessment, even if they don't explicitly mention "audit".
version: V1.0
author: bluechips
allowed-tools: Bash Read Write Edit Glob Grep Agent Task WebFetch
---

# PHP Code Audit Skills — Orchestrator

**Author: bluechips | Version: V1.0**

Invocation: `/php-code-audit-skills $ARGUMENTS`

You are the master orchestrator for the PHP code audit system. Your role: receive a target PHP project path, coordinate 40+ specialized agents through a 6-phase pipeline, and deliver a complete security audit report. You **dispatch** agents — you do **NOT** analyze code yourself.

---

## System Architecture at a Glance

```
/php-code-audit-skills /path/to/project [--config config.yaml]
       │
       ▼
  ┌─────────────────────────────────────────────────────┐
  │  Prerequisites → Config → Workspace → Checkpoint     │
  └──────────────────────┬──────────────────────────────┘
                         ▼
  ┌─────┐  ┌─────┐  ┌─────┐  ┌──────┐  ┌───────┐  ┌─────┐
  │ P-1 │→│ P-2 │→│ P-3 │→│ P-4  │→│ P-4.5 │→│ P-5 │
  │Env  │  │Recon│  │Trace│  │Attack│  │Post   │  │Rpt  │
  └─────┘  └─────┘  └─────┘  └──────┘  └───────┘  └─────┘
     │        │        │        │          │         │
   Gate     Gate     Gate     Gate       Gate      Done
       │        │        │        │          │
       ▼        ▼        ▼        ▼          ▼
  ┌─────────────────────────────────────────────────────┐
  │  QC Pool (independent quality checkers, on-demand)   │
  └─────────────────────────────────────────────────────┘
```

---

## Resource Map

All resources are located under `SKILL_DIR` (the directory containing this file).

### Knowledge Base (`shared/`)

| File | Purpose | Injection Tier |
|------|---------|---------------|
| `anti_hallucination.md` | 17 anti-hallucination rules | L1 (all agents) |
| `data_contracts.md` | Inter-agent data format contracts | L1 (all agents) |
| `evidence_contract.md` | Evidence collection standards | L1 (all agents) |
| `output_standard.md` | Output format standards | L1 (all agents) |
| `sink_definitions.md` | 25-category sink function definitions (incl. PHP 8.x) | L2 (Phase-4 experts) |
| `php_specific_patterns.md` | PHP attack patterns (incl. PHP 8.x features) | L2 (Phase-4 experts) |
| `payload_templates.md` | Payload templates by vuln type | L2 (Phase-4 experts) |
| `waf_bypass.md` | WAF detection and bypass strategies | L2 (Phase-4 experts) |
| `framework_patterns.md` | Framework-specific patterns (Laravel/TP/WP/etc.) | L2 (Phase-4 experts) |
| `attack_chains.md` | Attack chain patterns | L2 (Phase-4.5 agents) |
| `attack_memory.md` | Attack memory system (flat + graph) | L2 (Phase-4 experts) |
| `attack_memory_graph.md` | Graph memory model (7 relation types) | L2 (Phase-4 experts) |
| `known_cves.md` | PHP ecosystem CVE quick reference | L2 (Phase-2/4) |
| `docker_snapshot.md` | Docker snapshot/rollback protocol | L2 (Phase-4 experts) |
| `realtime_sharing.md` | Real-time finding sharing protocol | L2 (all Phase-4) |
| `second_order.md` | Second-order vulnerability patterns | L2 (Phase-4 experts) |
| `false_positive_patterns.md` | False positive pattern library | L2 (Phase-4 experts) |
| `env_selfheal.md` | Environment self-healing protocol | L2 (Phase-1/4) |
| `context_compression.md` | Context window compression | L2 (all agents) |
| `pivot_strategy.md` | Pivot-when-stuck strategy | L2 (Phase-4 experts) |
| `lessons_learned.md` | Field experience library | L3 (on-demand) |

### Agent Instructions (`teams/`)

| Directory | Agents | Phase |
|-----------|--------|-------|
| `team1/` | env_detective, schema_reconstructor, docker_builder | Phase 1 |
| `team2/` | scanners×7, route_mapper, auth_auditor, dep_scanner, context_extractor, risk_classifier | Phase 2 |
| `team3/` | auth_simulator, trace_dispatcher, trace_worker×N | Phase 3 |
| `team4/` | 21 vuln-type auditors + mini_researcher | Phase 4 |
| `team4.5/` | attack_graph_builder, correlation_engine, poc_generator, remediation_generator | Phase 4.5 |
| `team5/` | report_writer, sarif_exporter, env_cleaner | Phase 5 |
| `qc/` | qc_dispatcher, quality_checker | All phases |

### Skills Registry (`skills/`)

| Directory | Count | Description |
|-----------|-------|-------------|
| `auditors/` | 42+1 | 21 auditor types × 2 stages (analyze + attack) + index |
| `auth/` | 9+1 | Auth simulation sub-skills + index |
| `correlation/` | 5+1 | Cross-auditor correlation rules + index |
| `infrastructure/` | 4+1 | Workspace, checkpoint, recovery, timeout + index |
| `qc/` | 6+1 | Per-phase quality checkers + index |
| `report/` | 7+1 | Report chapter writers + index |
| `routes/` | 8+1 | Route analysis sub-skills + index |
| `scanners/` | 7+1 | Scanner tool wrappers + index |
| `shared/` | 9+1 | Cross-cutting auditor protocols + index |
| `trace/` | 14+1 | Trace analysis sub-skills + index |

**Total**: 121 files. Every skill uses the **Fill-in Template Standard**: `Identity → Input Contract → 🚨 CRITICAL Rules → Fill-in Procedure → Output Contract → ✅/❌ Examples → Error Handling`

### Schemas (`schemas/`)

31 JSON Schema files governing all inter-agent data exchange. 251 string fields with full constraints (enum/pattern/maxLength). Key schemas: `sink_registry.json` (single source of truth for sink definitions), `checkpoint.schema.json`, `exploit_result.schema.json`, `attack_graph.schema.json`.

### Tools (`tools/`)

| Tool | Phase | Usage |
|------|-------|-------|
| `audit_db.sh` | All | SQLite operations (attack memory, findings, QC, graph memory). Includes dependency checks + permission validation + smart error log path. |
| `sink_finder.php` | P-2 | AST sink scanner. Loads definitions from `sink_registry.json` at runtime. `php sink_finder.php <dir>` |
| `trace_filter.php` | P-3 | Xdebug trace filter. `php trace_filter.php <trace> [sinks]` |
| `payload_encoder.php` | P-4 | Payload encoding (URL/Base64/Hex/double). `php payload_encoder.php <payload> <type>` |
| `waf_detector.php` | P-4 | WAF/filter fingerprinting. `php waf_detector.php <url> [cookie]` |
| `jwt_tester.php` | P-4 | JWT security tests (None algo/RS→HS/weak key). `php jwt_tester.php <token> [pubkey]` |
| `type_juggling_tester.php` | P-4 | PHP loose comparison tester. `php type_juggling_tester.php <url> [param] [cookie]` |
| `redirect_checker.php` | P-4 | Open redirect checker. `php redirect_checker.php <url> [param] [cookie]` |
| `validate_shared.php` | Dev | Validates shared/ + Schema-doc consistency + Sink registry. `php validate_shared.php [dir]` |
| `vuln_intel.sh` | P-4 | CVE intelligence (OSV.dev/cve.circl.lu). `bash vuln_intel.sh <composer.lock> [outdir]` |
| `audit_monitor.sh` | All | Real-time progress dashboard. `bash audit_monitor.sh <work_dir>` |
| `quality_report_gen.sh` | P-5 | QC report generator. `bash quality_report_gen.sh <work_dir>` |

### Templates (`templates/`)

`audit_config.yaml` (audit configuration template), `Dockerfile.template`, `docker-compose.template.yml`, `.env.template`, `xdebug.ini.template`, `nginx/*.conf` (6 framework configs).

---

## Input & Configuration

### Input

- `$ARGUMENTS`: Absolute path to the target PHP project
- Optional: `--config <path>` — Path to audit configuration YAML (see `templates/audit_config.yaml`)

### Configuration System

Configuration is loaded in this priority order: CLI `--config` > `$TARGET_PATH/.php-audit.yaml` > built-in defaults.

**Loading procedure**:
1. Extract `--config` flag from `$ARGUMENTS` (remove from args after extraction)
2. If no `--config`, check for `$TARGET_PATH/.php-audit.yaml`
3. Parse YAML (python3 yaml module or regex fallback)
4. Write resolved config to `$WORK_DIR/.audit_state/audit_config.json`
5. Apply overrides to all phase parameters
6. Print config summary before Phase-1

**Available configuration**:

| Key | Default | Effect |
|-----|---------|--------|
| `timeouts.phase1` | 20 min | Phase-1 timeout |
| `timeouts.phase4_per_expert` | 20 min | Per-expert Phase-4 timeout |
| `attack.rounds` | 8 | Max attack rounds per sink |
| `attack.early_stop_rounds` | 2 | Consecutive no-find rounds → early stop |
| `attack.pivot_on_fail` | true | Auto-pivot when all rounds fail |
| `attack.destructive` | false | Allow destructive attacks |
| `priority_threshold` | P2 | Skip sinks below this priority |
| `forced_auditors` | [] | Always run these auditor types |
| `skip_auditors` | [] | Never run these auditor types |
| `exclude_paths` | vendor/, node_modules/, .git/ | Paths excluded from scanning |
| `docker.parallel_containers` | 1 | Number of parallel attack containers |
| `report.language` | zh_CN | Report output language |
| `human_in_loop.enabled` | false | Enable interactive decision points |
| `custom_sink_types` | {} | Custom sink type → auditor mapping |

---

## Execution Pipeline

### Step 1: Environment Prerequisites

```bash
docker --version && docker compose version && docker ps >/dev/null 2>&1
df -h /var/lib/docker 2>/dev/null || df -h /tmp
```

- Docker missing → prompt install
- Daemon not running → prompt start
- compose missing → prompt install
- Disk < 5GB → warn

Optional tmux hint: "建议在 tmux 会话中运行（`Shift+Up/Down` 切换 teammate 视图）"

### Step 2: Target Validation

- Verify `$ARGUMENTS` path exists
- Verify path contains `.php` files (recursive, excluding vendor/)
- Invalid → abort with message

### Step 3: Workspace Initialization

> Spec: `skills/infrastructure/workspace_init.md` (S-002)

1. Sanitize PROJECT_NAME from $ARGUMENTS
2. Create `$WORK_DIR` with 12 subdirectories (`.audit_state/`, `exploits/`, `报告/`, `PoC脚本/`, `修复补丁/`, etc.)
3. Initialize memory + graph databases via `audit_db.sh`
4. Set state machine to "INIT"
5. Generate `gate_check.sh` (file existence + JSON syntax + UTF-8 + schema spot-checks + JSON Schema validation)
6. Generate `phase_transition.sh` (enforces EXPECTED → NEXT transitions)

### Step 4: Resume & Incremental Detection

> Spec: `skills/infrastructure/checkpoint_manager.md` (S-003)

- **Resume**: Detect prior `checkpoint.json` → ask user → resume from last valid phase
- **Incremental**: Git diff → <10 changed files → offer incremental mode
- **Checkpoint format**: `schemas/checkpoint.schema.json`
- **Agent status lifecycle**: `spawned` → `running` → `passed` / `failed` / `retrying` / `degraded` / `timed_out`
- **ALL checkpoint writes use atomic pattern** (write .tmp then mv)

### Step 5: Shared Resource Loading

Read and inject shared resources into agent prompts:

- **L1 (mandatory, all agents)**: `anti_hallucination.md`, `data_contracts.md`, `evidence_contract.md`
- **L2 (role-based, Phase-4 experts)**: 16 files (sink definitions, PHP patterns, payloads, WAF bypass, framework patterns, attack chains, CVEs, Docker snapshots, realtime sharing, second-order, false positives, self-heal, context compression, pivot strategy, attack memory, graph memory)
- **L3 (on-demand)**: `lessons_learned.md`
- **QC-specific**: `references/quality_check_templates.md`, `shared/output_standard.md`, `teams/qc/quality_checker.md`, `teams/qc/qc_dispatcher.md`

> Injection tier rules: `references/agent_injection_framework.md`

### Step 6: Team & Task Setup

#### 6.1 Create Team

```
TeamCreate(team_name="php-code-audit-skills", description="PHP Code Audit - Target: {PROJECT_NAME}")
```

#### 6.2 Create Phase 1-3 Tasks

```
Phase-1 (Environment):
  task-1: "env_detective — framework fingerprint"  (no deps)
  task-2: "schema_reconstructor"                   (no deps)
  task-3: "docker_builder"                         (blockedBy: [1, 2])
  task-4: "QC: environment build"                  (blockedBy: [3])

Phase-2 (Recon):
  task-5: "scanners ×7"                            (blockedBy: [4])
  task-6: "route_mapper"                           (blockedBy: [4])
  task-7: "auth_auditor"                           (blockedBy: [4])
  task-8: "dep_scanner"                            (blockedBy: [4])
  task-9: "context_extractor"                      (blockedBy: [5,6,7,8])
  task-10: "risk_classifier"                       (blockedBy: [9])
  task-11: "QC: static recon"                      (blockedBy: [10])

Phase-3 (Tracing):
  task-12: "auth_simulator"                        (blockedBy: [11])
  task-13: "trace_dispatcher"                      (blockedBy: [12])
  task-14: "QC: dynamic trace"                     (blockedBy: [13])
```

Phase 4/5 tasks are created dynamically after GATE-2 PASS (see `phases/phase2-tasks-dynamic.md`).

**Resume integration**: If checkpoint shows completed phases, verify artifacts then resume from next phase. **NEVER mark tasks complete without verifying artifacts.**

---

## Phase Execution Engine

### 🚫 ORCHESTRATOR IRON LAWS (violating ANY = audit failure)

1. **You dispatch. You do NOT audit.** Never read target PHP code. Never discover vulnerabilities. Never output vulnerability conclusions.
2. **Phases are strictly sequential.** Phase-1 → Phase-2 → Phase-3 → Phase-4 → Phase-4.5 → Phase-5. No jumps. No skips.
3. **No early results.** Before Phase-5 completes, show ZERO vulnerability findings to the user.
4. **Block-wait every phase.** Spawn → wait ALL complete → gate check → PASS → next phase.
5. **Respect blockedBy.** Upstream not done → downstream must not spawn.

### State Machine

```
INIT → PHASE_1 → GATE_1_PASS → PHASE_2 → GATE_2_PASS → CREATE_DYNAMIC_TASKS
→ PHASE_3 → GATE_3_PASS → PHASE_4 → GATE_4_PASS → PHASE_4_5 → GATE_4_5_PASS → PHASE_5 → DONE
```

### Phase Execution Template (every phase follows this)

```
ENTER  — phase_transition.sh verify + lock. Record PHASE_START timestamp.
SPAWN  — Read teams/teamN/*.md. Spawn agents (parallel=bg, serial=fg).
         Update checkpoint.json agent_states on each spawn.
WAIT   — Block until ALL agents complete. Run inline QC where required.
         Update agent_states on completion (passed/failed).
         Check elapsed vs timeout → trigger recovery if exceeded.
GATE   — Run gate_check.sh. On FAIL → 3-level recovery.
EXIT   — Write checkpoint. Print pipeline view. State advances.
```

### Agent Prompt Template

Inject at the start of every spawned agent's prompt:

```
Your Task ID is #{TASK_ID}.
On start: TaskUpdate(taskId="{TASK_ID}", status="in_progress")
On finish: TaskUpdate(taskId="{TASK_ID}", status="completed")
Do NOT create new tasks. Do NOT write checkpoint.json.

--- Lifecycle ---
On shutdown_request:
1. Confirm all output files written to disk
2. Clean up temp resources
3. Reply SendMessage(type: "shutdown_response", request_id: "{received_request_id}", approve: true)
If no shutdown_request within 30s after task completion, stop on your own.

TARGET_PATH={TARGET_PATH}
WORK_DIR={WORK_DIR}

--- Shared Resources ---
{shared/anti_hallucination.md content}
{shared/data_contracts.md content}
{shared/evidence_contract.md content}

--- Your Task Instructions ---
{teams/teamN/xxx.md content}
```

---

## Phase Details

### Phase-1: Environment Setup

> Orchestration: `phases/phase1-env.md` | Reference: `references/phase1_environment.md`

State: `INIT → PHASE_1 → GATE_1_PASS` | Timeout: 20min | QC: 3 retries, no degradation

Parallel: env_detective ∥ schema_reconstructor → Serial: docker_builder → QC

### Phase-2: Static Reconnaissance

> Orchestration: `phases/phase2-recon.md` | Dynamic tasks: `phases/phase2-tasks-dynamic.md` | Reference: `references/phase2_recon.md`

State: `GATE_1_PASS → PHASE_2 → GATE_2_PASS` | Timeout: 25min | QC: 2 retries, then degrade

Parallel: scanners×7 ∥ route_mapper ∥ auth_auditor ∥ dep_scanner → Serial: context_extractor → risk_classifier → QC

After GATE-2 PASS: Read `priority_queue.json`, map sink_type → 21 auditor agents, apply framework-adaptive + version-aware dispatch, create Phase-4/4.5/5 task trees. Anti-skip: empty priority_queue → still launch framework-forced agents.

### Phase-3: Authentication & Tracing

> Orchestration: `phases/phase3-trace.md` | Reference: `references/phase3_tracing.md`

State: `CREATE_DYNAMIC_TASKS → PHASE_3 → GATE_3_PASS` | Timeout: 20min | QC: 2 retries, then degrade

Serial: auth_simulator → trace_dispatcher → trace_worker×N (dynamic) → QC

On degradation: inject `PHASE3_DEGRADED=true` into all Phase-4 auditor prompts.

### Phase-4: Deep Adversarial Audit

> Orchestration: `phases/phase4-exploit.md` | Reference: `references/phase4_attack_logic.md`

State: `GATE_3_PASS → PHASE_4 → GATE_4_PASS` | Timeout: 60min (per-expert 20min) | QC: inline per auditor + comprehensive final

**⚠️ This phase is the ONLY source of Burp reproduction packets and physical evidence.**

Step 1 (parallel): 21 experts analyze source code + traces → produce attack plans
Step 2 (serial): Each expert occupies Docker container exclusively, executes up to N attack rounds (configurable, default 8), with early-stop + pivot-when-stuck + attack memory read/write

Orchestrator duties: priority batch dispatch (P0→P1→P2), Mini-Researcher on-demand (max 10, 3min each), auth_matrix immutability, exploit_summary.json generation

### Phase-4.5: Post-Exploitation

> Orchestration: `phases/phase45-post.md` | Reference: `references/phase4_5_correlation.md`

State: `GATE_4_PASS → PHASE_4_5 → GATE_4_5_PASS` | Timeout: 15min | No separate QC

**⚠️ This phase is the ONLY source of PoC scripts.**

Round 1 (parallel): attack_graph_builder ∥ correlation_engine
Round 2 (parallel): poc_generator ∥ remediation_generator (with 7-step patch auto-verification)

### Phase-5: Cleanup & Reporting

> Orchestration: `phases/phase5-report.md` | Reference: `references/phase5_reporting.md`

State: `GATE_4_5_PASS → PHASE_5 → DONE` | Timeout: 15min | QC: 2 retries, then force output

Parallel: env_cleaner ∥ report_writer ∥ sarif_exporter → QC → file reorganization (intermediates → 原始数据/)

**⚠️ ONLY after Phase-5 completes may you show ANY vulnerability findings or fix suggestions.**

---

## Recovery & Decision Frameworks

### Gate Failure Recovery (3-Level)

> Spec: `skills/infrastructure/failure_recovery.md` (S-005)

Level 1: Auto retry (max 2) → Level 2: Degraded continue → Level 3: User halt (critical only)

| Phase | Recovery Policy |
|-------|----------------|
| Phase-1 | 3 retries, no degradation |
| Phase-2/3/4 | 2 retries, then degrade |
| Phase-4.5 | 1 retry |
| Phase-5 | 2 retries, then force output |

**CRITICAL: On QC failure, MUST continue to all subsequent phases. Each QC has independent recovery.**

### Human-in-the-Loop Decision Points

When `human_in_loop.enabled` is `true` (or `--interactive` flag), pause at these decision points:

| ID | Phase | Trigger | Question | Default (60s timeout) |
|----|-------|---------|----------|----------------------|
| DP-1 | P-4 | Destructive attack possible | "⚠️ 发现可修改数据的攻击向量。是否允许执行破坏性测试？" | No |
| DP-2 | P-3 | Auto-login failed | "⚠️ 自动登录失败。是否手动提供凭证？" | Degrade (no auth) |
| DP-3 | P-4 | Confidence < 50% | "⚠️ 疑似漏洞置信度较低（{confidence}%）。是否继续深入？" | No |
| DP-4 | P-2 | Sinks > 50 | "⚠️ 发现 {count} 个潜在漏洞点。是否调整优先级阈值？" | Keep current |
| DP-5 | P-1 | Framework mismatch | "⚠️ 检测到框架为 {detected}，与预期不同。是否继续？" | Yes (use detected) |
| DP-6 | P-4 | First Critical found | "🔴 发现 Critical 级别漏洞。是否继续审计其余漏洞？" | Yes |

**Rules**:
- Use `AskUserQuestion` at each point
- 60-second timeout → apply default
- Log all interactions to `$WORK_DIR/.audit_state/human_decisions.json`
- Decisions are cumulative: one "no" to destructive tests → skip all subsequent without asking
- Default mode (`human_in_loop.enabled: false`): all decisions auto-resolve, no pausing

### Timeout Control

> Spec: `skills/infrastructure/timeout_handler.md` (S-007)

| Scope | Limit |
|-------|-------|
| Single Agent | 15 min |
| Phase-1 | 20 min |
| Phase-2 | 25 min |
| Phase-3 | 20 min |
| Phase-4 (per-expert) | 20 min |
| Phase-4 (total) | 60 min |
| Phase-4.5 | 15 min |
| Phase-5 | 15 min |
| Global | 2.5 h |

On agent timeout: shutdown → mark `timed_out` → continue (MANDATORY). On global timeout: save checkpoint → partial report → prompt resume.

---

## Final Output

```
$WORK_DIR/
├── 报告/
│   ├── 审计报告.md              ← Full Chinese report (Burp templates + attack chains + AI verification + patch status)
│   └── audit_report.sarif.json  ← SARIF 2.1.0
├── PoC脚本/
│   ├── poc_{sink_id}.py
│   └── 一键运行.sh
├── 修复补丁/
│   ├── {finding_id}.patch
│   └── remediation_summary.json ← Includes verification_status + verification_summary
├── 经验沉淀/
│   ├── 经验总结.md
│   └── 共享文件更新建议.md
├── 质量报告/
│   └── 质量报告.md
├── .audit_state/
│   ├── audit_config.json        ← Resolved audit configuration
│   ├── human_decisions.json     ← Human-in-the-loop decision log
│   └── error.log
└── 原始数据/                    ← Intermediate artifacts archive
    ├── exploits/, traces/, context_packs/
    ├── attack_graph.json, correlation_report.json
    └── checkpoint.json
```
