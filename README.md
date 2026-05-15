<div align="center">

# 🛡️ PHP_code_audit_skills

**全链路 PHP 代码安全审计 AI Agent 系统**

**Author: bluechips**

![Version](https://img.shields.io/badge/version-V1.0-blue)
![Skills](https://img.shields.io/badge/skills-145+-green)
![Skill Files](https://img.shields.io/badge/skill_files-121-brightgreen)
![Auditors](https://img.shields.io/badge/auditors-21_types_×_2_stages-red)
![Schemas](https://img.shields.io/badge/schemas-31-orange)
![Phase](https://img.shields.io/badge/phases-6-purple)
![Controllability](https://img.shields.io/badge/controllability-560+_constraints-yellow)

基于 Claude Code Agent Teams 的多智能体协作安全审计框架。40+ 专业 Agent 协同工作，覆盖环境构建 → 静态侦察 → 动态追踪 → 深度对抗利用 → 后渗透关联分析 → 报告收口全链路，支持 **21 种漏洞类型** 专家级审计与 **PHP 8.x** 全版本安全覆盖。

[功能特性](#功能特性) · [快速开始](#快速开始) · [架构总览](#架构总览) · [Agent 编制](#agent-编制) · [输出产物](#输出产物)

</div>

---

## 功能特性

### 🔄 全链路自动化流水线

6 阶段严格顺序执行，每阶段 Gate 门禁强制验收，不可跳跃、不可省略：

```
Phase 1 环境构建 → Phase 2 静态侦察 → Phase 3 动态追踪
→ Phase 4 深度对抗 → Phase 4.5 后渗透分析 → Phase 5 报告收口
```

- **断点续审**：`checkpoint.json` 记录阶段状态，中断后可恢复
- **增量审计**：Git diff 检测变更，<10 文件变更时提供增量模式
- **错误自恢复**：DB 损坏、Agent 崩溃、Token 溢出、磁盘不足等 5 种异常场景自动恢复
- **配置文件驱动**：`audit_config.yaml` 自定义超时、轮数、优先级阈值、排除路径等全部参数

### 🎯 21 种漏洞类型 + PHP 8.x 全版本覆盖

| 分类 | 漏洞类型 |
|------|---------|
| **注入类** | RCE（命令/代码执行）、SQLi（一阶+二阶）、NoSQL（MongoDB/Redis）、XXE、LDAP、CRLF |
| **文件类** | LFI（本地/远程文件包含）、FileWrite（文件上传/写入） |
| **Web 类** | XSS、SSTI、SSRF（+DNS Rebinding）、CSRF |
| **逻辑类** | 越权/IDOR、业务逻辑缺陷、竞态条件（含 Fiber 并发） |
| **数据类** | 反序列化（+POP chain）、Session 管理缺陷 |
| **运维类** | 配置缺陷、弱加密/密钥泄露、信息泄露、日志注入 |
| **框架类** | WordPress 专有漏洞 |

**PHP 8.x 攻击面**：

| 特性 | 最低版本 | 安全风险 |
|------|---------|---------|
| Named Arguments Injection | 8.0 | 覆盖安全默认参数绕过 XSS 防护 |
| First-Class Callable Syntax | 8.1 | 绕过字符串回调检查实现 RCE |
| Fiber Concurrency | 8.1 | TOCTOU 竞态条件 |
| Enum::from() Type Confusion | 8.1 | 类型混淆与信息泄露 |
| Attribute Injection | 8.0 | 动态属性绕过鉴权 |
| Match Expression Coercion | 8.0 | 类型转换绕过严格比较 |

### 🧠 智能攻击记忆系统

双层记忆架构，跨项目经验积累：

- **扁平记忆**：`attack_memory` 表 — 按 sink_type + framework + PHP版本 + WAF 指纹匹配历史攻击经验
- **关系型图记忆**：`memory_nodes` + `memory_edges` 表 — 7 种实体关系类型，支持攻击链自动发现
- 跨项目学习：历史审计经验自动积累，新项目审计自动匹配相似模式

### 🔬 Mini-Researcher 智能研究员

5 种条件自动触发（未知组件、无 PoC 的 Critical CVE、连续 5 轮攻击失败等），本地知识库 → NVD/GitHub Advisory 外部情报 → 结构化输出，3 级置信度消费。

### ⚡ 混合调度 + 对抗循环

- **并行分析 + 串行攻击**：21 专家先并行静态分析，再逐个独占容器执行攻击
- **可配置攻击轮数**：默认 8 轮，通过配置文件调整（1-20），含早停机制
- **Pivot When Stuck**：连续失败自动转向（SQLi→二阶SQLi、XSS→SSTI、LFI→php://filter RCE 等）
- **框架感知调度**：Laravel / ThinkPHP / Symfony / WordPress 等框架特征识别与强制审计项
- **版本感知调度**：PHP < 8.0 → Type Juggling, ThinkPHP 5.x → RCE, PHP 8.1+ → Fiber/Enum 等

### 👤 人在回路（Human-in-the-Loop）

6 个关键决策点，默认关闭，开启后 60 秒超时自动使用默认值，决策累积（一次拒绝 = 后续同类自动跳过）：

| 决策点 | 阶段 | 触发条件 | 默认行为 |
|--------|------|---------|---------|
| DP-1 破坏性测试 | Phase-4 | 攻击可能修改/删除数据 | 拒绝 |
| DP-2 手动凭证 | Phase-3 | 自动登录失败 | 降级继续 |
| DP-3 低置信度深入 | Phase-4 | 置信度 < 50% | 跳过 |
| DP-4 范围调整 | Phase-2 | Sink > 50 个 | 保持当前阈值 |
| DP-5 框架不匹配 | Phase-1 | 检测与预期不符 | 使用检测到的框架 |
| DP-6 Critical 确认 | Phase-4 | 首个 Critical 漏洞 | 继续审计 |

### 🔒 质量保障体系

- **Gate 门禁**：每阶段结束强制校验产物存在性（GATE-1 ~ GATE-4.5）
- **独立 QC 池**：按需 spawn 质检员，"完成一个、校验一个"
- **31 个 JSON Schema**：所有 Agent 间数据交换严格校验格式，251 个 string 字段全部约束
- **Sink 注册表**：`sink_registry.json` 作为单一数据源，`sink_finder.php` 运行时加载
- **Schema-文档一致性校验**：`validate_shared.php` 自动检测 `data_contracts.md` 与 `schemas/` 的一致性
- **560+ 可控性约束**：填空模板 + 反幻觉规则 + Schema 校验，消除自由文本幻觉风险

### ✅ 修复补丁自动验证

Phase-4.5 生成 Patch 后自动执行 7 步验证：Apply → 验证应用 → 重放攻击 Payload → 检查漏洞修复 → 检查回归 → 回滚 Patch → 验证回滚。验证结果分类：`verified` / `partial_regression` / `not_fixed` / `apply_failed` / `rollback_failed`

### 📊 专业审计报告

单文件全包含报告，含：Context Pack 内嵌、Burp 复现模板、Mermaid 攻击链可视化、CVSS 进度条（`████████░░ 9.45/10`）、SARIF 2.1.0 导出、Patch 验证状态。

---

## 架构总览

### 阶段功能总览

| 阶段 | Agent 数 | 核心功能 | 关键产物 |
|------|---------|---------|---------|
| **Phase 1: 环境构建** | 3 | 框架识别、Schema 重建、Docker 构建 + 自愈 | `environment_status.json` |
| **Phase 2: 静态侦察** | 12 | 工具扫描（7 种）、路由映射、鉴权矩阵、依赖扫描、上下文抽取、风险定级 | `priority_queue.json`、`context_packs/` |
| **Phase 3: 动态追踪** | 3+N | 鉴权模拟、Xdebug 追踪、调用链校验 | `traces/*.json`、`credentials.json` |
| **Phase 4: 深度利用** | 21+1 | 21 类漏洞专家审计 + Mini-Researcher | `exploits/*.json`、`research/*.json` |
| **Phase 4.5: 后渗透** | 4 | 攻击图谱、关联分析、Patch 自动验证、PoC 生成 | `attack_graph.json`、`PoC脚本/*.py` |
| **Phase 5: 报告收口** | 3 | 报告生成、SARIF 导出、环境清理 | `报告/审计报告.md`、`.sarif.json` |
| **QC: 质检** | 2 | 独立质检员池、贯穿全流程 | QC 记录写入 `audit_session.db` |

### Skills 体系

145+ skills 组织在 `skills/` 的 10 个子目录中，采用 **2-Stage 审计员模式**（21 种审计员 × 2 阶段 = 42 文件）和标准化 **填空模板格式**。

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

**填空模板标准**：`Identity → Input Contract → 🚨 CRITICAL Rules → Fill-in Procedure → Output Contract → ✅/❌ Examples → Error Handling`

### 设计哲学

| 原则 | 含义 |
|------|------|
| 填空模板 > 自由生成 | 结构化字段减少 AI 幻觉 |
| 正反例 > 抽象规则 | 具体示例锚定行为 |
| 多Agent单一职责 > 单体 | 每个 Agent 只做一件事 |
| 独立QC不自审 | 质量校验由独立质检员执行 |
| AI指令英文，输出中文 | 模型精确性 + 用户可读性 |
| 单一数据源 | `sink_registry.json` 统一 Sink 定义 |
| 配置驱动 > 硬编码 | `audit_config.yaml` 让用户自定义审计参数 |

### 攻击循环

```
查询攻击记忆（扁平 + 图记忆）
  ↓
制定攻击计划 → exploit_plan.json
  ↓
Round 1~N 循环（默认 8 轮，可配置）：
  ① Docker 快照
  ② 发送 Payload
  ③ 采集物理证据（HTTP 响应/命令输出）
  ④ 成功 → 写入 exploit + 记忆
  ⑤ 失败 → WAF 分析 → 调整策略
  ⑥ 连续失败 → Pivot 转向
  ⑦ 触发条件 → Mini-Researcher 委派
  ⑧ 连续 N 轮无新发现 → 早停退出
  ↓
写入攻击记忆 → QC 质检 → 下一个 Sink
```

### Pivot 自动转向

| 原始攻击 | 转向目标 |
|----------|---------|
| SQLi 全部失败 | 二阶 SQLi（存储→读取→拼接） |
| XSS 被完全过滤 | SSTI（`{{7*7}}` 探测） |
| LFI 路径过滤 | `php://filter` chain RCE |
| RCE disable_functions | 反序列化 POP chain |
| SSRF 内网不可达 | DNS Rebinding |

### 修复补丁自动验证

```
E-1: git apply 应用 Patch
E-2: 验证应用成功
E-3: 重放攻击 Payload
E-4: 检查漏洞是否修复
E-5: 检查无回归
E-6: git apply -R 回滚 Patch
E-7: 验证回滚干净
→ 写入 verification_status 到 remediation_summary.json
```

跳过条件：非 Git 仓库 / 容器未运行 / 无成功 Payload / 配置变更类 Patch
回滚失败紧急恢复：`git checkout -- .`

---

## Agent 编制

### Team 1 — 环境构建（3 Agents）

| Agent | 职责 |
|-------|------|
| `env_detective` | 框架指纹、PHP 版本、DB 类型识别 |
| `schema_reconstructor` | 从 ORM 模型重建数据库表结构 |
| `docker_builder` | Docker 环境构建 + `env_selfheal` 自愈循环 |

### Team 2 — 静态侦察（12 Agents）

| Agent | 职责 |
|-------|------|
| `psalm_scanner` | Psalm taint analysis |
| `progpilot_scanner` | Progpilot vulnerability scan |
| `ast_scanner` | AST sink detection（从 `sink_registry.json` 加载定义） |
| `phpstan_scanner` | PHPStan static analysis |
| `semgrep_scanner` | Semgrep pattern matching |
| `composer_audit_scanner` | Composer dependency audit |
| `codeql_scanner` | CodeQL analysis (optional) |
| `route_mapper` | 路由表解析与映射 |
| `auth_auditor` | 鉴权机制分析 |
| `dep_scanner` | 第三方组件 CVE 检测 |
| `context_extractor` | Sink 上下文抽取 + 数据流分析 |
| `risk_classifier` | 风险优先级定级 P0/P1/P2/P3 |

### Team 3 — 动态追踪（3 + N Agents）

| Agent | 职责 |
|-------|------|
| `auth_simulator` | 模拟多角色登录获取凭证 |
| `trace_dispatcher` | 读取高危 Sink 分批创建追踪任务 |
| `trace_worker` ×N | Xdebug 追踪执行（动态创建） |

### Team 4 — 漏洞审计（21 + 1 Agents）

<details>
<summary>展开 21 种专家审计员</summary>

| # | Agent | 覆盖类型 |
|---|-------|---------|
| 1 | `rce_auditor` | 命令/代码执行（含 PHP 8.x NamedArgs/FirstClass） |
| 2 | `sqli_auditor` | SQL 注入（一阶 + 二阶） |
| 3 | `xss_ssti_auditor` | XSS + SSTI |
| 4 | `lfi_auditor` | 本地/远程文件包含 |
| 5 | `filewrite_auditor` | 文件上传/写入 |
| 6 | `ssrf_auditor` | SSRF + DNS Rebinding |
| 7 | `xxe_auditor` | XML 外部实体 |
| 8 | `deserial_auditor` | 反序列化 + POP chain |
| 9 | `crlf_auditor` | CRLF 注入 |
| 10 | `csrf_auditor` | 跨站请求伪造 |
| 11 | `authz_auditor` | 越权 + IDOR |
| 12 | `session_auditor` | Session 管理缺陷 |
| 13 | `crypto_auditor` | 弱加密/密钥泄露 |
| 14 | `race_condition_auditor` | 竞态条件（含 Fiber 并发） |
| 15 | `nosql_auditor` | MongoDB/Redis 注入 |
| 16 | `ldap_auditor` | LDAP 注入 |
| 17 | `infoleak_auditor` | 信息泄露 |
| 18 | `logging_auditor` | 日志注入/敏感日志 |
| 19 | `config_auditor` | 配置缺陷 |
| 20 | `wordpress_auditor` | WordPress 专有漏洞 |
| 21 | `business_logic_auditor` | 业务逻辑缺陷 |
| — | `mini_researcher` | 智能研究员（按需委派） |

</details>

### Team 4.5 — 后渗透分析（4 Agents）

| Agent | 职责 |
|-------|------|
| `attack_graph_builder` | 构建攻击图谱 + 链式利用路径 |
| `correlation_engine` | 跨审计员关联 + 图记忆消费 + 误报消除 |
| `poc_generator` | 可执行 PoC 脚本生成 |
| `remediation_generator` | 修复 Patch 生成（框架适配）+ 7 步自动验证 |

### Team 5 — 报告收口（3 Agents + 7 Chapter Writers）

| Agent | 职责 |
|-------|------|
| `report_writer` | 主审计报告编排（7 章并行写入 → 单文件组装） |
| `sarif_exporter` | SARIF 2.1.0 标准导出 |
| `env_cleaner` | Xdebug 清理 + 代码/数据库还原 |

**7 个 Chapter Writers**：`cover_page_writer`（封面+目录+摘要）、`vuln_summary_writer`（汇总表）、`vuln_detail_writer`（漏洞详情）、`attack_chain_writer`（联合攻击链）、`coverage_stats_writer`（覆盖率）、`risk_pool_writer`（风险池）、`lessons_writer`（经验总结）

### QC — 独立质检（2 Agents）

| Agent | 职责 |
|-------|------|
| `qc_dispatcher` | 质检任务分发 |
| `quality_checker` | 质量校验（含 Mini-Researcher + 图记忆专项） |

---

## 快速开始

### 前置要求

- **Docker** + **Docker Compose**（必需）
- **Claude Code**（v2.1.87+）
- **tmux**（可选，分屏查看并行 Agent）

> 本项目自带完整的多 Agent 编排（phases + teams + skills），无需依赖 Claude Code 的 Agent Teams 实验特性。如使用第三方 API，建议关闭 Agent Teams 以避免模型不兼容问题。

### 1. 准备环境

```bash
docker --version
docker compose version
```

### 2. 配置 Skill

将本仓库放入 Claude Code 的 skills 目录，或作为项目级 `.github/skills/PHP_code_audit_skills/` 使用。

### 3. 一键启动审计

```text
/php-code-audit-skills /path/to/php-project
```

系统自动执行 6 阶段全链路审计，输出完整报告和 PoC。

### 4. 自定义配置

在目标项目根目录创建 `.php-audit.yaml`，或使用 `--config` 指定配置文件：

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

**加载优先级**：CLI `--config` > 目标项目 `.php-audit.yaml` > 内置默认值

---

## 目录结构

```text
PHP_code_audit_skills/
│
├── SKILL.md                          # 主调度器（Skill 入口 + 配置文件支持 + 人在回路决策点）
├── README.md                         # 本文档
├── 全链路详细流程.md                    # 完整执行流程图（文字版）
│
├── phases/                           # 阶段执行模板（7 个）
│   ├── phase1-env.md                 #   环境智能识别与构建
│   ├── phase2-recon.md               #   静态资产侦察
│   ├── phase2-tasks-dynamic.md       #   动态侦察任务创建
│   ├── phase3-trace.md               #   鉴权模拟与动态追踪
│   ├── phase4-exploit.md             #   深度对抗审计
│   ├── phase45-post.md               #   后渗透智能分析（含 Patch 自动验证）
│   └── phase5-report.md              #   清理与报告收口
│
├── teams/                            # Agent 指令文件（40+ Agents）
│   ├── team1/                        #   环境构建（3）
│   ├── team2/                        #   静态侦察（5 dispatchers）
│   ├── team3/                        #   动态追踪（3+N）
│   ├── team4/                        #   漏洞审计（21+1）
│   ├── team4.5/                      #   后渗透分析（4，含 Patch 自动验证）
│   ├── team5/                        #   报告收口（3）
│   └── qc/                           #   质检（2）
│
├── shared/                           # 共享知识库（28 个）
│   ├── anti_hallucination.md         #   反幻觉规则（17 条铁律）
│   ├── php_specific_patterns.md      #   PHP 特有攻击模式（含 PHP 8.x）
│   ├── sink_definitions.md           #   Sink 函数定义（25 类，含 PHP 8.x Sink）
│   ├── attack_memory.md              #   攻击记忆系统（扁平 + 关系型）
│   ├── attack_memory_graph.md        #   关系型图记忆模型
│   ├── data_contracts.md             #   数据合约（引用 schemas/）
│   ├── evidence_contract.md          #   证据采集标准
│   └── ...                           #   其余 21 个共享知识文件
│
├── schemas/                          # JSON Schema（31 个）
│   ├── sink_registry.json            #   Sink 函数注册表（单一数据源）
│   └── ...                           #   其余 30 个 Schema
│
├── references/                       # 参考文档（9 个）
│   ├── agent_injection_framework.md  #   Agent 注入框架（L1/L2/L3）
│   └── ...                           #   其余 8 个参考文档
│
├── tools/                            # 辅助工具（12 个）
│   ├── audit_db.sh                   #   数据库操作（含依赖检查+权限校验+智能错误日志）
│   ├── sink_finder.php               #   AST Sink 扫描器（从 sink_registry.json 加载）
│   ├── trace_filter.php              #   Xdebug Trace 过滤器
│   ├── payload_encoder.php           #   Payload 编码器
│   ├── waf_detector.php              #   WAF 指纹识别
│   ├── jwt_tester.php                #   JWT 安全测试
│   ├── type_juggling_tester.php      #   PHP 类型混淆测试
│   ├── redirect_checker.php          #   开放重定向检测
│   ├── validate_shared.php           #   shared/ 校验 + Schema-文档一致性 + Sink注册表校验
│   ├── vuln_intel.sh                 #   漏洞情报收集
│   ├── audit_monitor.sh              #   审计监控
│   └── quality_report_gen.sh         #   QC 报告生成
│
├── templates/                        # 环境模板 + 配置模板
│   ├── audit_config.yaml             #   审计配置文件模板
│   ├── Dockerfile.template
│   ├── docker-compose.template.yml
│   ├── xdebug.ini.template
│   └── nginx/                        #   Nginx 框架适配配置
│
├── assets/                           # 可视化资源
├── agent-flow.mmd                    # Agent 执行流程图（Mermaid）
└── audit-flow.mmd                    # 审计流程图（Mermaid）
```

---

## 辅助工具详解

| 工具 | 用途 | 使用阶段 |
|------|------|----------|
| `audit_db.sh` | SQLite 数据库操作（含依赖检查+权限校验） | 全阶段 |
| `sink_finder.php` | AST Sink 扫描器（从 `sink_registry.json` 加载定义） | Phase-2 |
| `trace_filter.php` | Xdebug Trace 精简过滤器 | Phase-3 |
| `payload_encoder.php` | Payload 编码（URL/Base64/Hex/双重等） | Phase-4 |
| `waf_detector.php` | WAF/过滤器指纹识别 | Phase-4 |
| `jwt_tester.php` | JWT 安全测试 | Phase-4 |
| `type_juggling_tester.php` | PHP 类型混淆松散比较测试 | Phase-4 |
| `redirect_checker.php` | 开放重定向检测 | Phase-4 |
| `vuln_intel.sh` | 漏洞情报收集（NVD/GitHub Advisory） | Phase-4 |
| `audit_monitor.sh` | 审计进度实时监控 | 全阶段 |
| `quality_report_gen.sh` | QC 报告汇总生成 | Phase-5 |
| `validate_shared.php` | shared/ 完整性校验 + Schema 一致性 + Sink 注册表校验 | 开发/维护 |

### audit_db.sh 命令速查

```bash
# 攻击记忆
bash audit_db.sh init-memory                     # 初始化（自动含图记忆）
bash audit_db.sh memory-write '<json>'            # 写入攻击经验
bash audit_db.sh memory-query '<json>'            # 查询匹配经验
bash audit_db.sh memory-stats                     # 记忆库统计
bash audit_db.sh memory-maintain                  # 清理过期记忆

# 图记忆
bash audit_db.sh graph-node-write '<json>'        # 写入图节点
bash audit_db.sh graph-edge-write '<json>'        # 写入图边
bash audit_db.sh graph-neighbors <node_id>        # 查询邻居节点
bash audit_db.sh graph-by-data-object <obj>       # 按数据对象查询
bash audit_db.sh graph-export <WORK_DIR>          # 导出完整图数据

# 发现管理
bash audit_db.sh finding-write '<json>'           # 写入发现
bash audit_db.sh finding-read [sink_id]           # 读取发现
bash audit_db.sh finding-consume <sink_id>        # 消费发现

# 质检
bash audit_db.sh qc-write '<json>'                # 写入质检记录
bash audit_db.sh qc-read [phase]                  # 读取质检记录
```

---

## 输出产物

```
$WORK_DIR/
├── 报告/
│   ├── 审计报告.md              ← 全中文单文件报告
│   └── audit_report.sarif.json  ← SARIF 2.1.0
├── PoC脚本/
│   ├── poc_{sink_id}.py         ← 每个漏洞的 PoC
│   └── 一键运行.sh              ← 批量执行
├── 修复补丁/
│   ├── {finding_id}.patch       ← 框架适配修复
│   └── remediation_summary.json ← 含 verification_status + verification_summary
├── 经验沉淀/
│   ├── lessons_learned.md
│   └── 共享文件更新建议.md
├── 质量报告/
│   └── 质量报告.md
├── .audit_state/
│   ├── audit_config.json        ← 解析后的审计配置
│   ├── human_decisions.json     ← 人在回路决策记录
│   └── error.log                ← 错误日志
└── 原始数据/                    ← 中间产物归档
    ├── exploits/, traces/, context_packs/
    ├── attack_graph.json, correlation_report.json
    └── checkpoint.json
```

### 审计报告结构

```
审计报告.md
├── 封面（项目元数据 + CVSS可视化进度条）
├── 目录（7章锚点导航）
├── 执行摘要（整体风险等级 + 关键发现 + 审计范围）
├── 漏洞汇总表（CVSS进度条 + AI验证徽章 + Patch验证状态）
├── 漏洞详情 ×N
│   ├── 漏洞信息卡（等级/类型/路由/Sink/鉴权/优先级）
│   ├── 上下文包（入口→调用链→Sink + 中间件 + 过滤器 + 认证绕过）
│   ├── Mermaid 攻击链
│   ├── 数据流追踪（Source→Sink + file:line）
│   ├── Burp 复现模板（Repeater + Intruder）
│   ├── 攻击迭代记录
│   └── 修复方案（修复前 vs 修复后 + 验证状态）
├── 联合攻击链分析（Mermaid + 步骤表）
├── 审计覆盖率统计
├── 待补证风险池
├── 审计经验总结
└── 页脚（版本 + 时间 + 工具 + 配置摘要）
```

---

## Gate 门禁与 QC 策略

### Gate 强制验收

| Gate | 校验条件 |
|------|---------|
| GATE-1 | `environment_status.json` 存在 |
| GATE-2 | `priority_queue.json` + `context_packs/` 存在 |
| GATE-3 | `credentials.json` 存在 |
| GATE-4 | `exploits/*.json` 存在 |
| GATE-4.5 | `PoC脚本/*.py` 存在 |

### QC 降级策略

| 阶段 | 质检不通过处理 |
|------|-------------|
| Phase-1 | 发回重做（最多 3 次），自愈循环/用户介入 |
| Phase-2 | 定位责任 Agent 补充，标注覆盖率继续 |
| Phase-3 | 断链路由退回静态分析，不阻塞 |
| Phase-4 | 降级标注，不阻塞报告 |

---

## 知识注入架构

| 层级 | 注入时机 | 内容 |
|------|---------|------|
| **L1（强制）** | 所有 Agent 启动 | `anti_hallucination.md`、`evidence_contract.md`、`data_contracts.md`、`output_standard.md` |
| **L2（角色相关）** | Phase-4 专家启动 | `sink_definitions.md`（含 PHP 8.x Sink）、`payload_templates.md`、`attack_memory.md`、`attack_memory_graph.md`、`waf_bypass.md`、`php_specific_patterns.md`（含 PHP 8.x 攻击模式）等 16 个 |
| **L3（按需）** | 运行时触发条件 | `lessons_learned.md`、`mini_researcher.md` |

---

## 最佳实践

1. **完整源码审计** — 提供完整项目源码目录，减少漏报
2. **保留 Docker 环境** — 便于复现验证与物理证据采集
3. **Gate + Schema 校验** — 交付前确认产物完整性
4. **分级修复** — `confirmed` 优先修复，`suspected` 人工复核；参考 `verification_status`
5. **攻击记忆复用** — 保留 `attack_memory.db`，积累跨项目经验
6. **配置文件定制** — 根据项目特点创建 `.php-audit.yaml`，跳过不适用的审计员
7. **开启人在回路** — 对关键项目开启 `human_in_loop.enabled: true`，确保破坏性测试需确认

---

## 项目统计

| 类别 | 数量 |
|------|------|
| Skill 文件（`skills/`） | 121（111 skill + 10 index） |
| 漏洞审计员（2-Stage） | 21 types × 2 = 42 files |
| Skills 子目录 | 10 |
| JSON Schema | 31 个（含 `sink_registry.json`） |
| 共享知识库（`shared/`） | 28 个 |
| 阶段定义 | 7 个 |
| 参考文档 | 9 个 |
| 辅助工具 | 12 个 |
| 环境模板 | 11 个（含 `audit_config.yaml`） |
| 报告 Chapter Writers | 7 个 |
| 可控性约束 | 560+ 项 |
| Markdown 文件总计 | 210+ 个 |

---

## 许可证

本项目仅供安全研究和学习使用。请在授权范围内对目标系统进行审计。

---

## 代码审计培训推荐（备注申请：云梦推荐）

给想学代码审计的师傅们分享两位我自己跟过的师傅，排名不分先后，风格各有侧重：

- **知名小朋友**（公众号：进击安全）
  https://mp.weixin.qq.com/s/66orkKQxmIXlI5n4ap-kRQ

- **润霖**（公众号：闪石星曜CyberSecurity）
  https://mp.weixin.qq.com/s/ewR42cjiPr5ZqdgaMDkHRg

我自己的学习体验是：润霖师傅这边更偏体系化，适合前期搭建整体框架；知名小朋友师傅是全直播，互动和实战感更强，对新手也比较友好，学习过程中更容易拿到阶段性反馈。

### 知名小朋友联系方式：

![知名小朋友联系方式](assets/xiaopengyou-contact.jpg)
