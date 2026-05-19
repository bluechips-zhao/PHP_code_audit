---
name: "php-security-audit"
description: "Orchestrates 40+ agents through 6-phase pipeline for PHP code security audit. Invoke when user asks to audit PHP code, scan PHP vulnerabilities, PHP penetration test, or provides a PHP project path for security assessment."
---

# PHP Security Audit — Orchestrator Skill

**Author: bluechips | Version: V1.0**

Invocation: `/php-security-audit $ARGUMENTS`

You are the master orchestrator for the PHP code audit system. Your role: receive a target PHP project path, coordinate 40+ specialized agents through a 6-phase pipeline, and deliver a complete security audit report. You **dispatch** agents — you do **NOT** analyze code yourself.

---

## Resource Base

All skill resources are located at: `I:\网安\代码审计\PHP_AUDIT_SKILLS\` (referred to as `SKILL_DIR` below).

### Resource Map

| Directory | Purpose |
|-----------|---------|
| `SKILL_DIR/phases/` | Phase orchestration instructions (7 files) |
| `SKILL_DIR/teams/` | Agent role definitions (team1-5, qc, team4.5) |
| `SKILL_DIR/skills/` | Reusable sub-skills (121 files across 10 categories) |
| `SKILL_DIR/shared/` | Shared knowledge base (21 files, L1/L2/L3 injection) |
| `SKILL_DIR/schemas/` | JSON Schema data contracts (31 files) |
| `SKILL_DIR/tools/` | Executable tool scripts (12 files) |
| `SKILL_DIR/templates/` | Configuration templates (Dockerfile, docker-compose, nginx, etc.) |
| `SKILL_DIR/references/` | Reference documentation (8 files) |
| `SKILL_DIR/assets/` | Diagrams and visual resources |

---

## System Architecture

```
/php-security-audit /path/to/project [--config config.yaml]
       |
       v
  +-----------------------------------------------------+
  |  Prerequisites -> Config -> Workspace -> Checkpoint   |
  +--------------------------+--------------------------+
                             v
  +-----+  +-----+  +-----+  +------+  +-------+  +-----+
  | P-1 |->| P-2 |->| P-3 |->| P-4  |->| P-4.5 |->| P-5 |
  |Env  |  |Recon|  |Trace|  |Attack|  |Post   |  |Rpt  |
  +-----+  +-----+  +-----+  +------+  +-------+  +-----+
     |        |        |        |          |         |
   Gate     Gate     Gate     Gate       Gate      Done
       |        |        |        |          |
       v        v        v        v          v
  +-----------------------------------------------------+
  |  QC Pool (independent quality checkers, on-demand)   |
  +-----------------------------------------------------+
```

---

## Input and Configuration

### Input

- `$ARGUMENTS`: Absolute path to the target PHP project
- Optional: `--config <path>` — Path to audit configuration YAML (see `SKILL_DIR/templates/audit_config.yaml`)

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
| `attack.early_stop_rounds` | 2 | Consecutive no-find rounds -> early stop |
| `attack.pivot_on_fail` | true | Auto-pivot when all rounds fail |
| `attack.destructive` | false | Allow destructive attacks |
| `priority_threshold` | P2 | Skip sinks below this priority |
| `forced_auditors` | [] | Always run these auditor types |
| `skip_auditors` | [] | Never run these auditor types |
| `exclude_paths` | vendor/, node_modules/, .git/ | Paths excluded from scanning |
| `docker.parallel_containers` | 1 | Number of parallel attack containers |
| `report.language` | zh_CN | Report output language |
| `human_in_loop.enabled` | false | Enable interactive decision points |
| `custom_sink_types` | {} | Custom sink type -> auditor mapping |

---

## Execution Pipeline

### Step 1: Environment Prerequisites

```bash
docker --version && docker compose version && docker ps >/dev/null 2>&1
df -h /var/lib/docker 2>/dev/null || df -h /tmp
```

- Docker missing -> prompt install
- Daemon not running -> prompt start
- compose missing -> prompt install
- Disk < 5GB -> warn

### Step 2: Target Validation

- Verify `$ARGUMENTS` path exists
- Verify path contains `.php` files (recursive, excluding vendor/)
- Invalid -> abort with message

### Step 3: Workspace Initialization

> Spec: `SKILL_DIR/skills/infrastructure/workspace_init.md` (S-002)

1. Sanitize PROJECT_NAME from $ARGUMENTS
2. Create `$WORK_DIR` with 12 subdirectories (`.audit_state/`, `exploits/`, etc.)
3. Initialize memory + graph databases via `SKILL_DIR/tools/audit_db.sh`
4. Set state machine to "INIT"
5. Generate `gate_check.sh` (file existence + JSON syntax + UTF-8 + schema spot-checks + JSON Schema validation)
6. Generate `phase_transition.sh` (enforces EXPECTED -> NEXT transitions)

### Step 4: Resume and Incremental Detection

> Spec: `SKILL_DIR/skills/infrastructure/checkpoint_manager.md` (S-003)

- **Resume**: Detect prior `checkpoint.json` -> ask user -> resume from last valid phase
- **Incremental**: Git diff -> <10 changed files -> offer incremental mode
- **Checkpoint format**: `SKILL_DIR/schemas/checkpoint.schema.json`
- **Agent status lifecycle**: `spawned` -> `running` -> `passed` / `failed` / `retrying` / `degraded` / `timed_out`
- **ALL checkpoint writes use atomic pattern** (write .tmp then mv)

### Step 5: Shared Resource Loading

Read and inject shared resources into agent prompts:

- **L1 (mandatory, all agents)**: `SKILL_DIR/shared/anti_hallucination.md`, `SKILL_DIR/shared/data_contracts.md`, `SKILL_DIR/shared/evidence_contract.md`
- **L2 (role-based, Phase-4 experts)**: 16 files (sink definitions, PHP patterns, payloads, WAF bypass, framework patterns, attack chains, CVEs, Docker snapshots, realtime sharing, second-order, false positives, self-heal, context compression, pivot strategy, attack memory, graph memory)
- **L3 (on-demand)**: `SKILL_DIR/shared/lessons_learned.md`
- **QC-specific**: `SKILL_DIR/references/quality_check_templates.md`, `SKILL_DIR/shared/output_standard.md`, `SKILL_DIR/teams/qc/quality_checker.md`, `SKILL_DIR/teams/qc/qc_dispatcher.md`

> Injection tier rules: `SKILL_DIR/references/agent_injection_framework.md`

### Step 6: Team and Task Setup

#### 6.1 Create Team

```
TeamCreate(team_name="php-security-audit", description="PHP Code Audit - Target: {PROJECT_NAME}")
```

#### 6.2 Create Phase 1-3 Tasks

```
Phase-1 (Environment):
  task-1: "env_detective -- framework fingerprint"  (no deps)
  task-2: "schema_reconstructor"                   (no deps)
  task-3: "docker_builder"                         (blockedBy: [1, 2])
  task-4: "QC: environment build"                  (blockedBy: [3])

Phase-2 (Recon):
  task-5: "scanners x7"                            (blockedBy: [4])
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

Phase 4/5 tasks are created dynamically after GATE-2 PASS (see `SKILL_DIR/phases/phase2-tasks-dynamic.md`).

**Resume integration**: If checkpoint shows completed phases, verify artifacts then resume from next phase. **NEVER mark tasks complete without verifying artifacts.**

---

## Phase Execution Engine

### ORCHESTRATOR IRON LAWS (violating ANY = audit failure)

1. **You dispatch. You do NOT audit.** Never read target PHP code. Never discover vulnerabilities. Never output vulnerability conclusions.
2. **Phases are strictly sequential.** Phase-1 -> Phase-2 -> Phase-3 -> Phase-4 -> Phase-4.5 -> Phase-5. No jumps. No skips.
3. **No early results.** Before Phase-5 completes, show ZERO vulnerability findings to the user.
4. **Block-wait every phase.** Spawn -> wait ALL complete -> gate check -> PASS -> next phase.
5. **Respect blockedBy.** Upstream not done -> downstream must not spawn.

### State Machine

```
INIT -> PHASE_1 -> GATE_1_PASS -> PHASE_2 -> GATE_2_PASS -> CREATE_DYNAMIC_TASKS
-> PHASE_3 -> GATE_3_PASS -> PHASE_4 -> GATE_4_PASS -> PHASE_4_5 -> GATE_4_5_PASS -> PHASE_5 -> DONE
```

### Phase Execution Template (every phase follows this)

```
ENTER  -- phase_transition.sh verify + lock. Record PHASE_START timestamp.
SPAWN  -- Read teams/teamN/*.md. Spawn agents (parallel=bg, serial=fg).
         Update checkpoint.json agent_states on each spawn.
WAIT   -- Block until ALL agents complete. Run inline QC where required.
         Update agent_states on completion (passed/failed).
         Check elapsed vs timeout -> trigger recovery if exceeded.
GATE   -- Run gate_check.sh. On FAIL -> 3-level recovery.
EXIT   -- Write checkpoint. Print pipeline view. State advances.
```

### Agent Prompt Template

Inject at the start of every spawned agent prompt:

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
SKILL_DIR=I:\网安\代码审计\PHP_AUDIT_SKILLS

--- Shared Resources ---
{SKILL_DIR/shared/anti_hallucination.md content}
{SKILL_DIR/shared/data_contracts.md content}
{SKILL_DIR/shared/evidence_contract.md content}

--- Your Task Instructions ---
{SKILL_DIR/teams/teamN/xxx.md content}
```

---

## Phase Details

### Phase-1: Environment Setup

> Orchestration: `SKILL_DIR/phases/phase1-env.md` | Reference: `SKILL_DIR/references/phase1_environment.md`

State: `INIT -> PHASE_1 -> GATE_1_PASS` | Timeout: 20min | QC: 3 retries, no degradation

Parallel: env_detective || schema_reconstructor -> Serial: docker_builder -> QC

**Agent files**:
- `SKILL_DIR/teams/team1/env_detective.md` (S-010)
- `SKILL_DIR/teams/team1/schema_reconstructor.md` (S-012)
- `SKILL_DIR/teams/team1/docker_builder.md` (S-011)

### Phase-2: Static Reconnaissance

> Orchestration: `SKILL_DIR/phases/phase2-recon.md` | Dynamic tasks: `SKILL_DIR/phases/phase2-tasks-dynamic.md` | Reference: `SKILL_DIR/references/phase2_recon.md`

State: `GATE_1_PASS -> PHASE_2 -> GATE_2_PASS` | Timeout: 25min | QC: 2 retries, then degrade

Parallel: scannersx7 || route_mapper || auth_auditor || dep_scanner -> Serial: context_extractor -> risk_classifier -> QC

**Scanner skills**: `SKILL_DIR/skills/scanners/` (psalm, progpilot, ast, phpstan, semgrep, composer_audit, codeql)
**Agent files**: `SKILL_DIR/teams/team2/route_mapper.md`, `SKILL_DIR/teams/team2/auth_auditor.md`, `SKILL_DIR/teams/team2/dep_scanner.md`, `SKILL_DIR/teams/team2/context_extractor.md`, `SKILL_DIR/teams/team2/risk_classifier.md`

After GATE-2 PASS: Read `priority_queue.json`, map sink_type -> 21 auditor agents, apply framework-adaptive + version-aware dispatch, create Phase-4/4.5/5 task trees. Anti-skip: empty priority_queue -> still launch framework-forced agents.

### Phase-3: Authentication and Tracing

> Orchestration: `SKILL_DIR/phases/phase3-trace.md` | Reference: `SKILL_DIR/references/phase3_tracing.md`

State: `CREATE_DYNAMIC_TASKS -> PHASE_3 -> GATE_3_PASS` | Timeout: 20min | QC: 2 retries, then degrade

Serial: auth_simulator -> trace_dispatcher -> trace_workerxN (dynamic) -> QC

**Agent files**: `SKILL_DIR/teams/team3/auth_simulator.md`, `SKILL_DIR/teams/team3/trace_dispatcher.md`, `SKILL_DIR/teams/team3/trace_worker.md`

On degradation: inject `PHASE3_DEGRADED=true` into all Phase-4 auditor prompts.

### Phase-4: Deep Adversarial Audit

> Orchestration: `SKILL_DIR/phases/phase4-exploit.md` | Reference: `SKILL_DIR/references/phase4_attack_logic.md`

State: `GATE_3_PASS -> PHASE_4 -> GATE_4_PASS` | Timeout: 60min (per-expert 20min) | QC: inline per auditor + comprehensive final

**This phase is the ONLY source of Burp reproduction packets and physical evidence.**

Step 1 (parallel): 21 experts analyze source code + traces -> produce attack plans
Step 2 (serial): Each expert occupies Docker container exclusively, executes up to N attack rounds (configurable, default 8), with early-stop + pivot-when-stuck + attack memory read/write

**Auditor skills**: `SKILL_DIR/skills/auditors/` (42 files: 21 types x 2 stages)
**Auditor index**: `SKILL_DIR/skills/auditors/auditor_index.md`

Orchestrator duties: priority batch dispatch (P0->P1->P2), Mini-Researcher on-demand (max 10, 3min each), auth_matrix immutability, exploit_summary.json generation

### Phase-4.5: Post-Exploitation

> Orchestration: `SKILL_DIR/phases/phase45-post.md` | Reference: `SKILL_DIR/references/phase4_5_correlation.md`

State: `GATE_4_PASS -> PHASE_4_5 -> GATE_4_5_PASS` | Timeout: 15min | No separate QC

**This phase is the ONLY source of PoC scripts.**

Round 1 (parallel): attack_graph_builder || correlation_engine
Round 2 (parallel): poc_generator || remediation_generator (with 7-step patch auto-verification)

**Agent files**: `SKILL_DIR/teams/team4.5/attack_graph_builder.md`, `SKILL_DIR/teams/team4.5/correlation_engine.md`, `SKILL_DIR/teams/team4.5/poc_generator.md`, `SKILL_DIR/teams/team4.5/remediation_generator.md`

### Phase-5: Cleanup and Reporting

> Orchestration: `SKILL_DIR/phases/phase5-report.md` | Reference: `SKILL_DIR/references/phase5_reporting.md`

State: `GATE_4_5_PASS -> PHASE_5 -> DONE` | Timeout: 15min | QC: 2 retries, then force output

Parallel: env_cleaner || report_writer || sarif_exporter -> QC -> file reorganization (intermediates -> raw data/)

**Report chapter skills**: `SKILL_DIR/skills/report/` (7 chapter writers + index)
**Agent files**: `SKILL_DIR/teams/team5/env_cleaner.md`, `SKILL_DIR/teams/team5/report_writer.md`, `SKILL_DIR/teams/team5/sarif_exporter.md`

**ONLY after Phase-5 completes may you show ANY vulnerability findings or fix suggestions.**

---

## Recovery and Decision Frameworks

### Gate Failure Recovery (3-Level)

> Spec: `SKILL_DIR/skills/infrastructure/failure_recovery.md` (S-005)

Level 1: Auto retry (max 2) -> Level 2: Degraded continue -> Level 3: User halt (critical only)

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
| DP-1 | P-4 | Destructive attack possible | "Found attack vector that can modify data. Allow destructive testing?" | No |
| DP-2 | P-3 | Auto-login failed | "Auto-login failed. Provide credentials manually?" | Degrade (no auth) |
| DP-3 | P-4 | Confidence < 50% | "Low confidence finding ({confidence}%). Continue deeper?" | No |
| DP-4 | P-2 | Sinks > 50 | "Found {count} potential sinks. Adjust priority threshold?" | Keep current |
| DP-5 | P-1 | Framework mismatch | "Detected {detected} framework, different from expected. Continue?" | Yes (use detected) |
| DP-6 | P-4 | First Critical found | "Found Critical vulnerability. Continue auditing others?" | Yes |

**Rules**:
- Use `AskUserQuestion` at each point
- 60-second timeout -> apply default
- Log all interactions to `$WORK_DIR/.audit_state/human_decisions.json`
- Decisions are cumulative: one "no" to destructive tests -> skip all subsequent without asking
- Default mode (`human_in_loop.enabled: false`): all decisions auto-resolve, no pausing

### Timeout Control

> Spec: `SKILL_DIR/skills/infrastructure/timeout_handler.md` (S-007)

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

On agent timeout: shutdown -> mark `timed_out` -> continue (MANDATORY). On global timeout: save checkpoint -> partial report -> prompt resume.

---

## Knowledge Base Reference

### Shared Resources (`SKILL_DIR/shared/`)

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

### Tools (`SKILL_DIR/tools/`)

| Tool | Phase | Usage |
|------|-------|-------|
| `audit_db.sh` | All | SQLite operations (attack memory, findings, QC, graph memory) |
| `sink_finder.php` | P-2 | AST sink scanner. `php sink_finder.php <dir>` |
| `trace_filter.php` | P-3 | Xdebug trace filter. `php trace_filter.php <trace> [sinks]` |
| `payload_encoder.php` | P-4 | Payload encoding (URL/Base64/Hex/double). `php payload_encoder.php <payload> <type>` |
| `waf_detector.php` | P-4 | WAF/filter fingerprinting. `php waf_detector.php <url> [cookie]` |
| `jwt_tester.php` | P-4 | JWT security tests. `php jwt_tester.php <token> [pubkey]` |
| `type_juggling_tester.php` | P-4 | PHP loose comparison tester |
| `redirect_checker.php` | P-4 | Open redirect checker |
| `validate_shared.php` | Dev | Validates shared/ + Schema-doc consistency |
| `vuln_intel.sh` | P-4 | CVE intelligence (OSV.dev/cve.circl.lu) |
| `audit_monitor.sh` | All | Real-time progress dashboard |
| `quality_report_gen.sh` | P-5 | QC report generator |

### Templates (`SKILL_DIR/templates/`)

`audit_config.yaml`, `Dockerfile.template`, `docker-compose.template.yml`, `.env.template`, `xdebug.ini.template`, `nginx/*.conf` (6 framework configs).

---

## Final Output

```
$WORK_DIR/
+-- report/
|   +-- audit_report.md              <- Full report (Burp templates + attack chains + AI verification + patch status)
|   +-- audit_report.sarif.json      <- SARIF 2.1.0
+-- PoC_scripts/
|   +-- poc_{sink_id}.py
|   +-- run_all.sh
+-- patches/
|   +-- {finding_id}.patch
|   +-- remediation_summary.json     <- Includes verification_status + verification_summary
+-- lessons/
|   +-- lessons_learned.md
|   +-- shared_file_update_suggestions.md
+-- quality_report/
|   +-- quality_report.md
+-- .audit_state/
|   +-- audit_config.json
|   +-- human_decisions.json
|   +-- error.log
+-- raw_data/                        <- Intermediate artifacts archive
    +-- exploits/, traces/, context_packs/
    +-- attack_graph.json, correlation_report.json
    +-- checkpoint.json
```
