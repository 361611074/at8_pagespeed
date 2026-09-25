# 生命周期审计报告

> 规范 §17 / §18 / §35。结论全部基于 **1.7.5 核心源码实证 + 测试站实测**，非推测。

版本：1.1.0　　审计日期：2026-09-25

## 一、核心事实（源码实证）

### 1. 停用与卸载共用同一个钩子

`zb_system/function/c_system_plugin.php` 中 `DisablePlugin()` 内部会调用 `UninstallPlugin_<id>()`。

⇒ **「停用插件」同样会进入 `UninstallPlugin_at8_pagespeed()`**。

### 2. 真正的「删除应用」不触发该钩子

删除由 AppCentre 直接删除插件目录完成，**不触发** `UninstallPlugin_<id>()`。

### 3. 核心没有 `UpdatePlugin()` 函数

`c_system_plugin.php` 只定义 `InstallPlugin()` 与 `UninstallPlugin()`。
官方升级钩子仅由 `GET /zb_system/cmd.php?act=misc&type=updatedapp` 触发（浏览器执行 `<script src>`，
curl 不会跑 JS）。

⇒ **手动覆盖文件升级永远不会触发官方升级钩子**。

## 二、本插件的应对策略

| 阶段 | 行为 | 设计理由 |
|---|---|---|
| 安装 | `InstallPlugin_at8_pagespeed()` 幂等写入默认配置（仅补齐缺失键） | 重复安装安全 |
| 启用 | 注册 2 个过滤器，无其它副作用 | — |
| 停用 | `UninstallPlugin_at8_pagespeed()` **有意空实现** | 因为停用也走这个钩子，删配置 = 用户每次停用都丢设置 |
| 删除 | 同上（不触发） | 配置独立于目录保留 |
| 升级 | `at8_pagespeed_migrate_config()` 按 `ConfigVer` 逐级迁移 | 只补齐缺失键，不碰用户已存值 |
| 重装 | 沿用旧配置 | 配置存在 `zbp_config` 表，与目录无关 |

### 迁移的三处调用点（覆盖全部升级路径）

1. `InstallPlugin_at8_pagespeed()` —— 安装 / 停用后再启用
2. `UpdatePlugin_at8_pagespeed()` —— 官方升级钩子（防核心将来真加上）
3. `main.php` 设置页加载时 —— **手动覆盖文件升级时 ①② 都不会触发，由这里兜底**

## 三、实测结果（`_market_life_110.py` 20/20 PASS）

| 场景 | 结果 |
|---|---|
| 停用后前台不再注入 | PASS |
| 停用后配置保留 | PASS（`LIFE110` 标记值全程保留） |
| 重新启用后注入恢复 | PASS |
| 重新启用后配置仍保留 | PASS |
| 删除插件目录后前台 HTTP 200、无致命错误 | PASS |
| 删除后后台插件页可访问 | PASS |
| 删除后配置行仍在 | PASS |
| 重新安装 HTTP 200、落地 1.1.0 | PASS |
| 重装后可启用 | PASS |
| **重装后旧配置继续沿用** | PASS |
| 重装后 `ConfigVer` 仍为 3 | PASS |
| 人为把 `ConfigVer` 退回 2 → 访问设置页后兜底升回 3 | PASS |
| 上述过程未改动用户黑名单 | PASS |

## 四、配置清理的正确出口

由于生命周期钩子不能删配置，本插件提供显式出口：
**设置 → 高级 → 清除插件配置**（JS `confirm` 二次确认）。

- 只调用官方 `DelConfig('at8_pagespeed')`，该函数内部为 `$this->configs[$name]->Delete()`，
  **只作用于传入的 name**
- 不触碰系统配置、其它插件配置、文章 / 用户 / 附件数据
- 实测：清除后本插件配置行数 = 0，其它 219 行配置不受影响，文章数据不受影响

## 五、降级风险（§36）

`ConfigVer` 只增不减，且迁移**不删除任何已有键**。
即使管理员从 1.1.0 退回旧版本，旧版本读取配置时缺失的键会走各自的默认值，
不会因数据结构变化而崩溃。
