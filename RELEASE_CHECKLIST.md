# at8_pagespeed 1.0.3 — 发布检查清单（RELEASE_CHECKLIST）

检查基准：《Z-BlogPHP 插件 AI Agent 开发规范》§29
实测环境：Z-BlogPHP **1.7.5** / PHP 7.3.4（本地 `php -l`）+ PHP 8.2（测试站 zblog.xmm.fan）/ MySQL
检查日期：2026-09-23

## 1. 元数据与标识

| 项 | 值 | 结果 |
|---|---|---|
| 插件 ID | `at8_pagespeed` | ✅ 长期稳定，未随重构改名 |
| 插件名称 | 页面加速 | ✅ |
| 版本号 | 1.0.3（`plugin.xml` 与 `AT8_PAGESPEED_VERSION` 一致） | ✅ 十进制封十进一 |
| 目录 / 文件前缀 | 函数 `at8_pagespeed_*`、CSS `.ps-*`、JS `window.at8Ps*`、配置键 `conf_Name=at8_pagespeed` | ✅ 无通用命名 |
| `plugin.xml` 必填节点 | id/name/url/note/description/path/include/level/author/source/adapted/version/pubdate/modified/price/**phpver**/advanced | ✅ 齐全 |
| 作者与官网 | 漫步白月光 / https://www.at8.fun/ | ✅ |
| `<source>` 来源名称 | 漫步白月光（**1.0.3 修正**：原为模板遗留值，与 `<author>` 不一致） | ✅ 已一致 |

## 2. 最低系统与 PHP 要求

| 项 | 值 | 依据 |
|---|---|---|
| 最低 Z-BlogPHP | 1.7.x | 依赖 `Filter_Plugin_Zbp_MakeTemplatetags`、`CheckIsRefererValid`、`$zbp->ismanage`、`Config()` 单参属性式 |
| 最低 PHP | **5.6**（`plugin.xml` 显式声明；打包脚本读取，不再写死 5.2） | 未使用 PHP 7 专有特性；实测 7.3.4 与 8.2 通过 |
| 数据库 | MySQL / SQLite / PostgreSQL | 不建表、不写库结构，仅用官方配置存取 |
| 第三方依赖 | 仅内置 instant.page v5.2.0（MIT，本地文件，无 Composer、无 CDN） | §23 |

## 3. 合规（无遗留项）

- [x] 无测试密钥、无硬编码 Token / 密码（全文件扫描 0 处）
- [x] 无 Debug 残留（`var_dump` / `print_r` / `error_log` 0 处）
- [x] 无 localhost、无开发 URL（0 处）
- [x] 未修改 Z-BlogPHP 核心文件（`zb_system/` 零改动）
- [x] 无虚构 Hook：仅使用 `Filter_Plugin_Zbp_MakeTemplatetags`、`Filter_Plugin_Admin_LeftMenu`（均经 1.7.5 源码确认存在）；已删除对不存在的 `Filter_Plugin_Zbp_Header/Footer` 的描述
- [x] 无 `!important`（CSS 全文件 0 处）
- [x] 无加密 / 混淆 PHP
- [x] 文件编码 UTF-8 无 BOM

## 4. 生命周期（测试站实测）

| 阶段 | 检查内容 | 结果 |
|---|---|---|
| 安装 | 幂等：仅补齐缺失配置键，不覆盖用户已保存值 | ✅ 重复安装后自定义值保留，`ConfigVer` 写入 |
| 启用 | 仅注册输出过滤器 + 后台菜单，无其他副作用 | ✅ |
| 正常运行 | 前台注入（header 预取 / footer 脚本）；后台、登录页、接口不注入 | ✅ 39 项断言全过 |
| 停用 | **不丢配置**（1.7.5 停用会触发 `UninstallPlugin` 钩子） | ✅ Bug 已复现并修复，见下 |
| 重新启用 | 配置沿用，前台立即恢复注入 | ✅ delay=180 + 黑名单全部保留 |
| 升级 | `UpdatePlugin_at8_pagespeed()` 按 `ConfigVer` 逐级迁移 | ✅ 入口就位（后续加键时追加分支） |
| 卸载 | 本插件不建表不写文件；配置保留为站点级偏好 | ✅ 已在代码注释说明取舍理由 |

### 本次修复的数据丢失问题（已复现 + 已修复）

1.7.5 的 `DisablePlugin()` 内部会调用 `UninstallPlugin_<id>()`，即**「停用」与「卸载」共用同一钩子**；真卸载（AppCentre 删应用）反而只删目录、不触发该钩子。
1.0.0 在该钩子里调用 `DelConfig()` → 每次停用清空全部设置。实测证据：

```
[复现] 1.0.0 include.php：停用前 delay=180、配置行 7 → 停用后 delay=''、配置行 0
[修复] 1.0.1 include.php：停用前 delay=180 → 停用后 delay=180、配置行 7 → 重启用后仍 180、前台生效
```

## 5. 安全

| 项 | 检查 | 结果 |
|---|---|---|
| 权限 | 后台页 `CheckRights('admin')` + `CheckPlugin()` | ✅ |
| CSRF | 写操作 `CheckIsRefererValid()`（失败 `ShowError(5)` 终止），表单 token 用无参 `GetCSRFToken()` | ✅ 合法 token 保存生效；篡改 token 被拦且配置未变 |
| XSS | 前台输出 `htmlspecialchars`（含 `lang` 属性）；DNS 域名白名单正则；黑名单 `json_encode` 注入 | ✅ |
| 开放重定向 | 已移除 `Redirect($_SERVER['HTTP_REFERER'])`，固定回设置页 | ✅ 跳转目标实测为本站设置页 |
| SSRF | 无任何外部 HTTP 请求 | ✅ N/A |
| 文件上传 / 文件系统 | 不涉及 | ✅ N/A |
| 路径穿越 | 不涉及 | ✅ N/A |
| 输出注入 | 黑名单关键字限长 100 且按行白名单化；域名去协议去路径 + 格式校验 | ✅ 非法域名实测被丢弃 |

## 6. 性能

- [x] 无 N+1 查询（不访问数据库，仅读 1 次插件配置，且 `Config()` 请求内缓存）
- [x] 无远程请求（脚本全部本地）
- [x] 前台输出为纯字符串拼接，无循环 IO
- [x] 脚本 `defer` 加载，不阻塞渲染

## 7. 测试与验收记录

- 本地：`php -l` 全通过，`node --check` 通过（3 个 JS），`plugin.xml` 可解析，`!important` = 0
- 测试站（zblog.xmm.fan）：**39 项断言 39 PASS / 0 FAIL**，含 debug 模式下 5 个页面零 `Notice/Warning/Deprecated`
- 安装回归（1.0.3）：**25 项断言 25 PASS / 0 FAIL**（干净包经官方「应用中心 → 上传应用」链路实装；落地清单与包内清单严格相等；无 `.git` 残留；额外落地文件仅 `cache/`，属运行时缓存）
- **升级回归（1.0.2 → 1.0.3，官方上传应用链路覆盖安装）**：版本常量与 `plugin.xml` 同步为 1.0.3 ✅ / 配置 7 项全部保留 ✅ / 9 个落地文件与包内清单一致 ✅
- **前台注入实测（1.0.3）**：`<head>` 注入 `<!-- at8_pagespeed 1.0.3 -->` + `x-dns-prefetch-control` + `link[rel=dns-prefetch]`；`</body>` 前注入 `at8PsLazySkip` / `at8-lazy.js` / `at8PsBlacklist` / `at8-guard.js` / `instantpage.js`（含 `data-instant-intensity="180"`）—— 与代码预期逐项一致
- **debug 模式实测**：页面内 `Fatal error` / `Parse error` / `Warning` / `Deprecated` / `Notice` / `Uncaught` 关键字**零命中** ✅
- 界面回归（1.0.2）：**17 项断言 17 PASS / 0 FAIL** —— 设置页已接入官方后台框架，`admin2.css` / `zblogphp.js` 已加载、顶栏 `#topmenu` 与左侧菜单 14 项（含 `nav_at8_pagespeed`）正常渲染、仅 1 个 doctype、无 PHP 告警、保存往返正常
- 布局对齐实测（1440×900）：`#divMain` 宽 1280 / 起点 x=150 / `max-width:none`、内容容器内距 `20px 24px 60px` / `max-width:1400px` —— 与 `at8_media_library` 设置页逐项一致
- 回归复现脚本：`audit_pagespeed.py`、`audit_repro_disable.py`、`test_zba_install_clean.py`、`deploy_ps_102.py`、`shot_release_ps.py`（截图）

## 8. 发布物

- `at8_pagespeed_1.0.3_20260923.zba`（**25.4 KB，9 文件**；真实插件文件 + `LICENSE`，已剔除 README / CHANGELOG / RELEASE_CHECKLIST / `screenshots` / zbignore / `cache` / `.git`）
  > ⚠️ 打包污染教训：本插件目录内就是 git 工作区，早期打包脚本只按 `zbignore.txt` 排除，
  > 导致 `.git` 整棵树（35 个文件、约 51 KB）被打进分包。现已在 `build_zba.php` 加入
  > 「不依赖 zbignore 的强制排除清单」，并对包内文件做逐文件 MD5 校验（`_verify_zba.py`）。
- 上架截图 `screenshots/`（不进分包，仅供应用中心上传）：`01-后台设置页.png`（1600×1310）、
  `02-前台页面-加速生效.png`（1600×1000）、`03-前台注入优化代码.png`（1600×485）
- GitHub：https://github.com/361611074/at8_pagespeed

## 9. Z-Blog 应用中心上架自检（对官方《发布应用》标准逐条）

依据：https://docs.zblogcn.com/php/dev-publish 与《应用审核拒绝标准》。

### A. 通用

| 标准 | 自检结果 |
|---|---|
| 开启 debug 模式后不得报错 | ✅ 测试站 debug 插件开启状态下，前台与后台页面 `Fatal/Parse/Warning/Deprecated/Notice/Uncaught` 零命中 |
| 不得含有木马等有害代码 | ✅ 全部源码人工可读，无 eval / base64 解码执行 / 混淆 |
| 不得含有被加密的 PHP | ✅ 全明文提交，无 Z5 或任何加密 |
| PHP 文件须 UTF-8 无 BOM | ✅ 全量检测 0 处 BOM |
| 不得有安全漏洞（SQL 注入 / XSS / CSRF） | ✅ 无 SQL 拼接；输出全部转义；写操作 `CheckIsRefererValid()` + `CheckRights('admin')` |
| **不得引用外站资源** | ✅ 全部资源本站内置（`assets/` 本地文件 + 站内绝对路径）；instant.page 为本地文件而非 CDN |
| 自动审核不得有黄色提示（老旧 JS 等） | ⚠️ 内置 instant.page v5.2.0（上游最新版，MIT，本地文件）；如自动审核报提示，按意见替换版本 |
| 不得跳过应用中心支付系统搞内置收费 | ✅ 完全免费，无任何付费校验或外部接口 |
| 不得修改系统源码或默认语言包 | ✅ `zb_system/` 与语言包零改动 |

### C. 插件专项

| 标准 | 自检结果 |
|---|---|
| 数据库表与 Class 使用 zbp 标准规范 | ✅ 不建表、不自建 Class |
| 数据库操作走系统链式对象，不自行拼接 SQL | ✅ 不直接操作数据库，仅用 `$zbp->Config()` 官方配置存取 |
| 应用 `$zbp->CheckRights` 判权限，而非 `$zbp->User->Level` | ✅ `main.php` 用 `CheckRights('admin')`，且未出现裸 `User->Level` 判定 |

### D. 应用发布内容

| 标准 | 自检结果 |
|---|---|
| 后台截图（有后台配置须提供） | ✅ `01-后台设置页.png` |
| 前台展示截图 | ✅ `02-前台页面-加速生效.png`（前台外观不受影响）+ `03-前台注入优化代码.png`（注入内容证据） |
| 基本 / 详细使用说明与功能介绍 | ✅ `plugin.xml` 的 `<description>` 详细说明三大功能；`README.md` 含功能、安装、生命周期、安全说明 |

### E. 禁止条款

| 标准 | 自检结果 |
|---|---|
| 禁止抄袭复制有版权保护的主题模板 | ✅ 原创实现；第三方仅 instant.page（MIT，已保留原始许可头并在 `LICENSE` 声明）；**1.0.3 已清除 `<source>` 中与他人标识冲突的模板遗留值** |
| 禁止多次提交无意义应用刷排行 | ✅ 首次提交本应用 |
