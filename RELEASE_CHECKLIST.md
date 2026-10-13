# at8_pagespeed 1.1.1 — 发布检查清单（RELEASE_CHECKLIST）

**版本**：1.1.1（`plugin.xml` / `AT8_PAGESPEED_VERSION` / `CHANGELOG.md` / `README.md` / 本文件 **五处一致**）
**检查日期**：2026-10-13
**实测环境**：Z-BlogPHP **1.7.5 Build 3510**（官方发布包 `zblogphp-1.7.5.3510.zip`，`blogversion=173510`）+ **PHP 8.2.33**（Windows 便携版）+ **SQLite** + 内置服务器（`php -S 127.0.0.1:8899`）
**检查基准**：《Z-BlogPHP 插件 AI Agent 开发规范》§29 + 应用中心上架审核清单

> **本清单的编写原则**
> 1. **只记录 2026-10-13 本次实际重新执行并通过的断言**，共 **224 项**（S0–S9 运行期 109 项 + A–E 静态 59 项 + 发布物打包与安装验收 47 项 + 远程真机 9 项），全部 PASS / 0 FAIL。
> 2. 往届版本（1.0.x）的测试结论**不再直接沿用**；未随本次重新执行的项已单独列在 **§10**，需要时须重跑后方可引用。
> 3. 每条断言都有稳定编号（如 `S5.2`、`A.6`），可用 §11 的命令复现。

---

## 1. 元数据与标识（实测 A 节 10/10）

| 项 | 值 | 结果 |
|---|---|---|
| 插件 ID | `at8_pagespeed` | ✅ 未改名 |
| 插件名称 | 页面加速 | ✅ |
| 版本号 | `1.1.1` | ✅ `A.1`/`A.2`/`A.3`/`A.5` 四处一致（`plugin.xml` `<version>`、`include.php` 常量、`CHANGELOG.md` 首条、`README.md` 更新日志首条） |
| 版本号格式 | 十进制封十进一（1.1.1） | ✅ `A.5` 无 `1.1.10` 式写法 |
| 命名前缀 | 函数 `at8_pagespeed_*`、常量 `AT8_PAGESPEED_VERSION`、CSS `.at8ps-*`、JS 全局 `window.at8Ps*`、配置命名空间 `conf_Name=at8_pagespeed` | ✅ 全量扫描无通用命名 |
| `plugin.xml` 必填节点 | id/name/url/note/description/path/include/level/author/source/adapted/version/pubdate/modified/price/phpver/advanced | ✅ 齐全 |
| 作者 / 来源 | 漫步白月光 / https://www.at8.fun/（`<author>` 与 `<source>` 一致） | ✅ |
| 日期一致性 | `pubdate=2026-09-23`、`modified=2026-10-13`，与 CHANGELOG/README 的 1.1.1 日期一致 | ✅ `A.8`/`A.9` |
| 本文件版本声明 | = `plugin.xml` `<version>` | ✅ `A.4` |

> **修正记录**：1.0.9 版清单曾把 CSS 前缀写作 `.ps-*`；1.1.1 已统一为 `.at8ps-*`，本文件按实际代码更新。

## 2. 最低系统与 PHP 要求（实测 A.6 / A.7 + S8）

| 项 | 值 | 依据（本次实测） |
|---|---|---|
| 最低 Z-BlogPHP | **1.7.5 Build 3510**（`<adapted>173510</adapted>`） | ① `A.6`：从 1.7.5.3510 的 `c_system_version.php` 实读 `MAJOR=1 / MINOR=7 / COMMIT=3510 / BUILD=5`，按核心 `blogversion = MAJOR.MINOR.COMMIT` 拼接推导得 `173510`，与 `<adapted>` **完全一致**（BUILD 位确实不参与）；② `S8.3`：构造 `adapted=173600` 的探针插件，在该基准站启用时被核心 `CheckCompatibility()` 拒绝（HTTP 500，错误 78「该应用需要…及以上版本的Z-BlogPHP」），**证明该值确实起「最低版本门槛」作用**；③ `S8.4`：对照 `adapted=173510` 启用放行 |
| 最低 PHP | **7.4**（`<phpver>` 显式声明） | ① `E.1`/`E.2`：包内全部 PHP 文件在 PHP 8.2.33 通过 `php -l`；② `E.4`：全量扫描无 PHP 8.0+ 专有语法（`?->` / `match` / `str_contains` / `enum` / `readonly` / 构造器提升 / 联合类型 / `#[Attribute]` 均 0 命中）；③ `S9`：运行时（PHP 8.2.33 + debug 模式）9 个场景零报错 |
| 数据库 | MySQL / SQLite / PostgreSQL | 不建表、不改表（`D.1` 零命中），仅用官方 `$zbp->Config()` 存取；本次在 **SQLite** 站点实测全流程 |
| 第三方依赖 | 仅内置 instant.page v5.2.0（MIT，本地文件） | ✅ `D.5` 包内 JS 仅 3 个（`instantpage.js` + 自研 `at8-guard.js` / `at8-lazy.js`）；`D.6` `instantpage.js` MD5 = 声明值；`D.7` 无外站 CDN 引用 |

## 3. 官方接口复核（实测 B 节 23/23）

**只使用 2 个官方 Hook**，均在 1.7.5.3510 源码中逐一确认存在：

| Hook | 用途 | 结果 |
|---|---|---|
| `Filter_Plugin_Zbp_MakeTemplatetags` | 前台 `header` / `footer` 追加输出 | ✅ `B.1` |
| `Filter_Plugin_Admin_LeftMenu` | 后台左侧菜单项 | ✅ `B.1` |

**插件引用的全部官方符号均存在**（无虚构 API）：

- `$zbp` 成员 13 个（`B.4`）：`CheckPlugin` / `CheckRights` / `Config` / `DelConfig` / `SaveConfig` / `GetCSRFToken` / `SetHint` / `ShowError` / `Load` / `host` / `ismanage` / `path` / `guid`
- 全局函数 6 个（`B.4b`）：`RegisterPlugin` / `Add_Filter_Plugin` / `CheckIsRefererValid` / `MakeLeftMenu` / `GetVars` / `Redirect`
- 核心函数存在性（`B.2`）：`RegisterPlugin` / `Add_Filter_Plugin` / `CheckIsRefererValid` / `CheckCSRFTokenValid` / `MakeLeftMenu` / `GetVars` / `Redirect` / `GetCSRFToken`
- `$zbp` 方法存在性（`B.3`）：`CheckPlugin` / `CheckRights` / `Config` / `DelConfig` / `SaveConfig` / `GetCSRFToken` / `SetHint` / `ShowError` / `Load`

**边界确认**：`B.5` 未读取 `$auth->Code` / `$zbp->user->Password` 等敏感字段；`B.6` 未使用 `$zbp->LoadApp()` / 手动 `new App` 等内部构造（不绕开官方链路）。

## 4. 合规确认（实测 C/D/E 节）

### 4.1 无核心文件修改（与官方发布包逐字节对比）

方法：解压官方 `zblogphp-1.7.5.3510.zip`，与测试站对应目录做「文件集合 + 逐文件 MD5」双向比对。

| 目录 | 结果 |
|---|---|
| `zb_system/` | ✅ `C.1` 无新增 / 无删除 / 无修改（`added=0 removed=0 changed=0`） |
| `zb_install/` | ✅ `C.1` 同上 |
| `zb_users/theme/default/`（官方默认主题） | ✅ `C.1` 同上 |
| `zb_users/theme/WhitePage/`（官方发行主题） | ✅ `C.1` 同上 |

> 该比对同时构成「默认语言包未被改动」的证据（语言文件位于 `zb_users/language/`，属 `zb_users/` 下未参与改动的官方文件；插件目录为唯一新增项）。

### 4.2 无自建重复业务系统（实测 D 节，范围 = 实际发包的 11 个文件）

| 检查 | 结果 |
|---|---|
| `D.0` 审计范围 = 发包文件集（与 `S1.3` 包内清单同源） | ✅ 11 个 |
| `D.1` 无 `CREATE/ALTER/DROP TABLE`、`CREATE INDEX` | ✅ 不建表、不自建存储 |
| `D.2` 无 `setcookie` / `session_start` / `password_hash` / `password_verify` | ✅ 不存在第二套用户/权限/会话系统 |
| `D.3` 无 `move_uploaded_file` / `fopen` / `file_put_contents` / `fwrite` | ✅ 不自建上传与文件管理 |
| `D.4` 无 `curl_init` / `fsockopen` / `stream_context_create` | ✅ 无任何出站请求 |
| `D.8` `LICENSE` + `THIRD-PARTY-LICENSES.md` 在包内 | ✅ |

### 4.3 无未经许可的第三方代码

| 检查 | 结果 |
|---|---|
| `D.5` 包内 JS 仅 `assets/instantpage.js`（第三方）+ `at8-guard.js` / `at8-lazy.js`（自研） | ✅ |
| `D.6` `instantpage.js` MD5 = `9b05e316aedabe445698f148c00e9e5e`，与 `THIRD-PARTY-LICENSES.md` 声明值一致 | ✅ |
| `D.7` 无外站 CDN / 远程脚本引用 | ✅ |
| `E.6` 无硬编码密码 / Token / 密钥 | ✅ |

### 4.4 代码质量与编码（实测 E 节 12/12）

`E.1`/`E.2` `php -l` 全通过 ｜ `E.3` 无 BOM ｜ `E.4` 无 PHP 8.0+ 语法 ｜ `E.5` 无 `var_dump` / `print_r` / `error_log` / `!important` / `eval` / `base64_decode` / `assert` ｜ `E.6` 无硬编码凭据

## 5. 打包与发布物（实测 S1 / S6 + 产物校验）

### 5.1 官方打包器实测（`S1` 7/7）

| 检查 | 结果 |
|---|---|
| `S1.1` 官方 `zb_users/plugin/AppCentre/app_pack.php` 打包成功，gzip 魔数 `1f8b` | ✅ |
| `S1.2` 包为官方 app XML 结构（`<app version="php" type="plugin">`，`<id>at8_pagespeed</id>`） | ✅ |
| `S1.3` 包内清单 = 仓库文件按 `zbignore.txt` 排除结果（**11 个**），`docs/` `scripts/` `dist/` `screenshots/` `.git` 均不在包内 | ✅ |
| `S1.4` 无发布禁止项（`CHANGELOG.md` / `RELEASE_CHECKLIST.md` / `zbignore.txt` / `release-*` / `.git`） | ✅ |
| `S1.5` 包内每个文件与仓库**逐字节一致**（逐文件 MD5 比对） | ✅ |
| `S1.6` 包内 `plugin.xml` 元数据：`version=1.1.1` / `adapted=173510` / `phpver=7.4` | ✅ |
| `S1.7` `README.md` / `LICENSE` / `logo.png` / `THIRD-PARTY-LICENSES.md` 在包内 | ✅ |

### 5.2 包内清单（11 个文件，与 `S1.3` 一致）

```
at8_pagespeed/plugin.xml
at8_pagespeed/include.php
at8_pagespeed/main.php
at8_pagespeed/style.css
at8_pagespeed/logo.png
at8_pagespeed/README.md
at8_pagespeed/LICENSE
at8_pagespeed/THIRD-PARTY-LICENSES.md
at8_pagespeed/assets/instantpage.js
at8_pagespeed/assets/at8-guard.js
at8_pagespeed/assets/at8-lazy.js
```

**包外（按 `zbignore.txt`）**：`CHANGELOG.md`、`RELEASE_CHECKLIST.md`、`release-manifest.txt`、`release-forbidden.txt`、`zbignore.txt`、`docs/`、`scripts/`、`dist/`、`screenshots/`、`cache/`、`.git/`

### 5.3 最终发布物（`dist/at8_pagespeed_1.1.1_20261013.zba`）

| 项 | 值 |
|---|---|
| 文件 | `dist/at8_pagespeed_1.1.1_20261013.zba` |
| 体积 | **51594 B** |
| MD5 | `51bf0d348855ae896c7a3b166824fc95` |
| SHA256 | `06402310ce600abe698e826ac69d51fd0dc5766865e06ad9e526e2e29072783e` |
| 文件数 | 11（与 §5.2 清单一致，逐文件 MD5 与仓库相等） |
| 元数据 | `version=1.1.1` / `adapted=173510` / `phpver=7.4` / `pubdate=2026-09-23` / `modified=2026-10-13` |
| `<verify>` | `base64("https://www.at8.fun/\n")` —— 正式站 host + 根目录空 path |
| 产出方式 | 官方 `App::Pack()` + `gzencode($s, 9, FORCE_GZIP)` |
| 解压后 XML | 118259 B；源文件总字节 **86052** |

> **本版取代 1.1.0 发布包**：`dist/at8_pagespeed_1.1.0_20260925.zba`（51017 B / MD5 `e0058763e5d2d476ad7960ff23a83ca0`）的 `include.php` 仍是 `isset` 版本，**不得继续分发**。该文件已从工作区移除（仍可从 git 历史 `8e5c105` 取回），对外分发以本版为准。

### 5.4 发布物打包与安装验收（47/47 PASS）

**为什么 `<verify>` 需要单独处理**（1.7.5.3510 源码实锤）：

- `App::Pack()` 写入 `<verify>base64_encode($zbp->host . "\n" . $zbp->path)</verify>`；
- `$zbp->path = $GLOBALS['blogpath'] = ZBP_PATH`（**站点文件系统路径**），`$zbp->host` 由 `$_SERVER['HTTP_HOST']` 自动识别；
- 因此在本地测试站直接打包会得到 `http://127.0.0.1:8899/` + 本机路径，把测试环境痕迹带进发布物；
- 核心 `zblogphp.php:825` 本意提供 `ZC_PERMANENT_DOMAIN_FORCED_URL` 强制域名，但它以 `GetValueInArray($this->option, ...)` 读取，而 `$zbp->option` 是对象、`GetValueInArray()` 对非数组入参**直接返回 default** ⇒ **该分支在 1.7.5 中不可达**（已实测：写入该配置项后打包，`<verify>` 仍为测试站指纹）；
- 故改用一次性入口 `_pack_release.php`：**仍调用官方 `App::Pack()` + `gzencode()`**，仅把 `Pack()` 所读取的 `host` / `path` 设为正式站值；该入口打包后即删除，不进仓库。

**打包断言（`pack_release.py`，24/24 PASS）**：

| 组 | 内容 | 结果 |
|---|---|---|
| `R0` | 后台登录可用；仓库源码同步到测试站插件目录；打包入口就位 | 3/3 ✅ |
| `R1` | 基线包由官方 HTTP 入口 `app_pack.php` 产出（gzip）；其 `<verify>` 为测试站指纹（对照用） | 2/2 ✅ |
| `R2` | 发布包由官方 `App::Pack()` 产出；`<verify>` == `"https://www.at8.fun/\n"`，与 git 中历史发布物一致 | 3/3 ✅ |
| `R3` | `<app version="php" type="plugin">` 结构；`id`/`version`/`adapted`/`phpver`/`name`/`path`/`include`/`pubdate`/`modified`/`price` 元数据；包内清单 == 仓库应发包清单（严格相等）；逐文件 MD5 与仓库一致；无禁止项；`include.php` 含 `array_key_exists` 修复且 MD5 与仓库一致 | 12/12 ✅ |
| `R4` | 与基线包做**差分**：除 `<verify>` 外 XML 完全相同、11 个文件内容完全一致 → 证明官方打包链路未被人为改动 | 2/2 ✅ |
| `R5` | 落盘 `dist/`；打包辅助入口删除、站点无残留 | 2/2 ✅ |

**安装验收（`verify_release.py`，用上表这个具体产物走官方链路，23/23 PASS）**：

| 组 | 内容 | 结果 |
|---|---|---|
| `V0`–`V1` | 产物存在且含 11 文件；回到干净状态（目录移除、不在启用列表、前台零输出） | 6/6 ✅ |
| `V2` | 官方 `app_upload.php` 上传 → `App::UnPack` 落盘 11 文件，与包内**逐字节一致**；上传本身不触发生命周期钩子 | 4/4 ✅ |
| `V3`–`V5` | 官方 `PluginEnb` 启用（`adapted=173510` 放行）；前台 10 项注入特征齐全且版本标识为 1.1.1；设置页 200 且接入官方后台框架、配置初始化 7 行 | 6/6 ✅ |
| `V6` | 停用 → 前台停注入；重启用后配置逐行沿用、前台恢复注入 | 3/3 ✅ |
| `V7`–`V8` | 官方 `app_del.php` 卸载后目录整体移除、配置按设计保留、前台正常；随后复原站点 | 4/4 ✅ |

**结论**：`dist/` 内分包为**含 `array_key_exists` 修复的 1.1.1 版本**，`<verify>` 为正式站指纹，可直接用于发布。1.1.0 的发布包为修复前构建，已由本版取代并从工作区移除。

**连带影响（已处理）**：`docs/MARKET_AUDIT_REPORT.md`、`docs/PACKAGE_AUDIT_REPORT.md` 审计的是 **1.1.0** 构建，已在两份报告**文首加注**「当前发布物为 1.1.1」并给出本版体积 / MD5；两份报告的正文断言**未随 1.1.1 重新执行**（见 §10）。

## 6. 官方安装链路全流程实测（S2–S9，运行期 105/105）

全程走官方链路，**不使用**任何私有部署脚本：
上传 `app_upload.php`（multipart + `token=GetToken('AppCentre')`）→ 启用 `cmd.php?act=PluginEnb` → 停用 `cmd.php?act=PluginDis` → 删除 `app_del.php` → 打包 `app_pack.php`。

| 阶段 | 断言数 | 结果 | 关键结论 |
|---|---|---|---|
| `S2` 干净状态基线 | 3/3 | ✅ | 未安装时前台零输出、插件列表无此项、`zbp_config` 无本插件行 |
| `S3` 上传→启用→设置页 | 27/27 | ✅ | 落地文件与包内清单**严格相等**；上传**不触发生命周期钩子**（不写配置）；启用后前台 10 项注入特征全过；设置页接入官方后台框架（`divMain` + 左侧菜单 + 官方 CSS）；进入设置页初始化 7 行配置（6 键 + `ConfigVer=3`）；后台页与登录页均不注入 |
| `S4` 设置页写入与边界 | 21/21 | ✅ | 见 §7.1 |
| `S5` 停用与重启用 | 9/9 | ✅ | 停用后配置**逐行保留**、前台停止注入、设置页被 `CheckPlugin` 拦截；重启用后配置原样沿用 |
| `S6` 覆盖升级 1.0.9 → 1.1.1 | 15/15 | ✅ | 见 §7.2 |
| `S7` 卸载与重装 | 9/9 | ✅ | 删除应用后目录移除、配置按设计保留、其它插件配置不受影响；重装后**自动沿用旧配置** |
| `S8` `adapted` 门槛反向验证 | 5/5 | ✅ | `adapted=173600` 被拒（错误 78）／`173510` 放行 |
| `S9` debug 模式零报错 | 9/9 | ✅ | 6 个页面 + 保存 + 清除配置，八类报错（`Fatal`/`Parse`/`Warning`/`Notice`/`Deprecated`/`Strict Standards`/`Uncaught`/`Undefined`）**零命中**；结束时已还原 `ZC_DEBUG_MODE=false` |

### 6.1 前台注入实测（`S3.5`，10 项特征全过）

启用后前台 `header` 注入 `<!-- at8_pagespeed 1.1.1 -->` + `<meta http-equiv="x-dns-prefetch-control" content="on">` + `<link rel="dns-prefetch">`（本站域名）；
`footer` 注入 `window.at8PsLazySkip` + `at8-lazy.js`（`defer`）+ 内联写 `document.body` 的 `data-instant-intensity` + `window.at8PsBlacklist` / `window.at8PsPreloadDelay` + `at8-guard.js`（`defer`）+ `instantpage.js`（`defer`）；资源地址为**站内绝对地址**（`$zbp->host` 拼接）。

### 6.2 主题兼容实测（含本次修复的缺陷复现）

| 断言 | 主题 | 结果 |
|---|---|---|
| `S3.15` | 官方默认主题 `default`（**不**自行填充 `$zbp->header`） | ✅ 1.1.1 正常注入（修复前为 0 注入） |
| `S3.16` | 第三方主题 `tpure`（自行填充 `$zbp->header`） | ✅ 正常注入 |
| `S3.15a` / `S3.16a` | 主题切换本身已生效（官方 `cmd.php?act=ThemeSet`，读回 `ZC_BLOG_THEME` 校验） | ✅ |
| `S6.5b` | **1.0.9** + 官方默认主题 | ✅ **0 处注入** —— 缺陷实锤复现 |
| `S6.5c` | 1.0.9 + `tpure` | ✅ 恢复注入（说明差异只来自主题，与切换动作无关） |

> 缺陷根因：1.7.5 核心 `$zbp->header` / `$zbp->footer` 属性默认值为 `null`，`MakeTemplateTags()` 中 `templateTags['header'] = &$zbp->header` 是**指向 null 的引用**，`isset()` 对 null 引用恒为 `false`。1.1.1 改为 `array_key_exists` 校验键存在，追加仍用 `.=`（null 安全转字符串）。

### 6.3 远程真机测试（`zblog.xmm.fan`，本次以 1.1.1 复跑）

环境：宝塔 Debian 12 + nginx + **Z-BlogPHP 1.7.5 Build 3540**（`blogversion=173540`）+ **PHP 8.3.33** + **MySQL 5.7**；主题 `tpure`；以 **1.1.1** 源码部署，站点文件与仓库**逐字节一致**（`include.php` md5 `eca5c0af9443cec95bb4ff1524ed53d4`、`plugin.xml` md5 `5713fa268c98748217960572ba4af57c`、`main.php` md5 `168d568ca199df699b11ee3ae8cc4535`）。

**零报错证明（日志差分法）**：先对 `zb_users/logs/*.txt` 做快照，再访问本插件相关页面，最后差分。

| 断言 | 内容 | 结果 |
|---|---|---|
| `R6.3.1` | 当日错误日志中 `at8_pagespeed` 出现次数 | ✅ **0 次**（当日共 149 条 `ERROR` + 30 条 `EXCEPTION`） |
| `R6.3.2` | 全历史日志中 `at8_pagespeed` 仅出现 6 次，均为 **2026-09-23** 的 `preg_replace(): Unknown modifier ']'`；该缺陷在当前版本已修（分隔符由 `/` 改为 `@`：`preg_replace('@[/?#].*$@', ...)`） | ✅ |
| `R6.3.3` | 页面遍历：前台首页 / 文章页 / 后台首页 / 插件管理 / 设置页 / debug 插件页 / 媒体库页 → 全部 HTTP 200，且均无 `Fatal error` `Warning` `Notice` `Deprecated` | ✅ |
| `R6.3.4` | 与 **`debug` 插件 v1.0.4 共存无冲突**：同一页既有 debug 面板（`debug_content` / `debug_window`）又有本插件全部注入标记与后台框架 | ✅ |
| `R6.3.5` | 前台注入特征齐全，版本标识 `<!-- at8_pagespeed 1.1.1 -->` | ✅ |
| `R6.3.6` | 保存自定义值（`preload_delay=321` / `lazy_skip=7` / `dns_domains=img.example.com` / 黑名单含 `mytest`）落库且前台生效 | ✅ |
| `R6.3.7` | 「清除插件配置」后本插件配置行归零（0 行）；重访设置页回填 7 行默认值（`preload_delay=65`） | ✅ |
| `R6.3.8` | 篡改 `csrfToken` 的保存被官方 `CheckIsRefererValid` 拦截（HTTP 500「非法访问」），且配置未被改动 | ✅ |
| `R6.3.9` | **对照实验**：先停用本插件，再切到官方默认主题 `default` → 前台仍为 HTTP 500（「主题模板的编译文件不存在」，出自 `zb_system/function/lib/template.php`）⇒ **与本插件无关**；根因是 `zb_users/cache/compiled/` 下只有 `tpure/`，其它主题从未编译过。实验后已还原（`tpure` + 启用） | ✅ 已排除 |

> 该站当日全部错误的来源为：`cache/compiled/tpure/index.php`(80)、`c_system_common.php`(28)、`theme/tpure/include.php`(24)、`c_system_route.php`(14，404 路由)、`AppCentre`(16)、`lib/template.php`(5)、`theme/Zit/include.php`(3)、`at8_media_library/api.php`(3)、`c_system_event.php`(3)、`admin/index.php`(2)、`lib/config.php`(1)。均为 PHP 8.3 的 `E_DEPRECATED`（`json_decode` / `explode` / `trim` / `substr` / `urlencode` / `htmlspecialchars` 传 `null`）与路由 404，来自**旧插件与主题**，非本插件。

> 说明：因该站 `default` 等主题缺少编译缓存，**真机主题矩阵无法复跑**；§6.2 的主题结论仍以本地 1.7.5.3510 站点为准。测试结束后站点已还原为 `tpure` + 插件启用 + 前台 200 正常注入。

## 7. 安全实测

### 7.1 设置页写入与输入边界（`S4` 21/21）

| 断言 | 检查 | 结果 |
|---|---|---|
| `S4.1` | 合法 `csrfToken` 保存生效，302 回本页 | ✅ |
| `S4.2`/`S4.3` | 自定义值落库并在前台生效（延迟 222 / 跳过 9 / 黑名单 / DNS 域名） | ✅ |
| `S4.4` | **篡改** `csrfToken` 的保存被官方 `CheckIsRefererValid()` 拦截（HTTP 500「非法访问」）且配置未变 | ✅ |
| `S4.5` | **缺失** `csrfToken` 同样被拦截且配置未变 | ✅ |
| `S4.6` | 写操作只认 POST（GET 携带 `act=save` 不产生写入） | ✅ |
| `S4.7`–`S4.9` | 边界钳制：`99999→2000`、`999→20`、`-5→0`、`-1→0`、`"abc"→0`、`"1.9"→1` | ✅ 不落脏值 |
| `S4.10` | `delay=0` 为合法值（前台输出 `intensity=0`，未被误判为关闭） | ✅ |
| `S4.11`/`S4.12` | DNS 恶意输入（`<script>`、`javascript:`、协议、路径、端口、空格、`http://` 空前缀）保存即丢弃；前台 DNS 输出仅含归一后的合法域名 | ✅ |
| `S4.13`/`S4.14` | 黑名单保存语义：空行丢弃、`trim`、CRLF 归一、`>100` 字符行丢弃；前台为 JSON 数组、顺序原样、**不追加任何条目** | ✅ |
| `S4.15`/`S4.16` | 黑名单含 `</script>` 可保存，前台以 `\u003C` 转义输出，**未发生 script 上下文 breakout** | ✅ |
| `S4.17`/`S4.18` | `act=reset` 清除配置：本插件配置行全删，系统与其它插件配置**逐行不变** | ✅ |
| `S4.19` | 清除配置同样受 CSRF 保护（篡改 token 被 500 拦截，配置不被删除） | ✅ |
| `S4.20`/`S4.21` | 清除后重开设置页按 migrate 重建默认值（7 行），前台恢复默认输出 | ✅ |

### 7.2 覆盖升级（`S6` 15/15）

用 `git tag v1.0.9` 导出真实旧版源码 → 官方 `app_pack` 打出 1.0.9 包（10 文件）→ 官方上传安装并启用 → 自定义配置 → 官方上传 1.1.1 包覆盖升级。

| 断言 | 检查 | 结果 |
|---|---|---|
| `S6.2`/`S6.3` | 1.0.9 包结构正确、安装落地 10 文件 | ✅ |
| `S6.4` | 1.0.9 启用成功（`adapted=173510` 在基准站放行） | ✅ |
| `S6.6` | 1.0.9 下自定义配置落库（延迟 333 / 跳过 11 / `oldonly` / `old.example.com`） | ✅ |
| `S6.7` | 1.1.1 覆盖升级成功，落地文件 = 1.1.1 包内清单（**11 个，严格相等**） | ✅ |
| `S6.8`/`S6.9` | 升级后配置**原样保留**、仍处启用态、配置行数不膨胀（7 行） | ✅ |
| `S6.10` | 版本标识更新为 1.1.1（注释与 `js?v=1.1.1`），旧 1.0.9 标识不再出现 | ✅ |
| `S6.11`/`S6.12` | 手动覆盖升级场景下，进入设置页触发型幂等迁移（`ConfigVer=3`、行数仍 7、用户黑名单不被改动），设置页回显旧配置 | ✅ |

> 说明：核心没有 `UpdatePlugin()` 函数，官方升级钩子由后台 `misc&type=updatedapp` 路由（浏览器执行）触发，**手动覆盖文件升级不会触发**。因此迁移在 `InstallPlugin_` / `UpdatePlugin_` / 设置页加载**三处**调用，`S6.11` 验证的正是第三条路径。

### 7.3 卸载与重装（`S7` 9/9）

`S7.1` 删除前需先停用（`App::CanDel()` 要求未启用）→ `S7.2` 目录整体移除 → `S7.3` 配置**按设计保留** → `S7.4` 其它插件与系统配置逐行不变 → `S7.5` 前台正常无报错 → `S7.6` 插件管理页正常 → `S7.7` 重新上传安装 → `S7.8`/`S7.9` **自动沿用旧配置**（444 / 跳过 13 / `keepme` / `keep.example.com`）。

> 设计取舍：1.7.5 的「停用」与「卸载」共用 `UninstallPlugin_<id>()` 钩子（`DisablePlugin()` 内部调用它），而真正的「删除应用」由 AppCentre 直接删目录、**不触发**该钩子。因此该钩子内**不得删除配置**——否则用户每次停用都会丢设置。`S5.2` / `S5.7` 即为该结论的实测证据。

## 8. 生命周期验收清单（对照实测断言）

判定标准即通过条件；括号内为本次实测通过的断言编号。

### 8.1 全新安装
- [x] **A1** 官方上传安装成功，无报错 —— 文件数 = 包内 11（`S3.1`/`S3.2`）
- [x] **A2** 启用后前台立即生效（出现 `<!-- at8_pagespeed 1.1.1 -->`）（`S3.4`/`S3.5`）
- [x] **A3** 配置初始化正确（≥7 行且含 `ConfigVer`）（`S3.9`）
- [x] **A4** 默认值符合预期（预加载开、懒加载开、延迟 65ms、跳过首屏 2 张、7 条通用黑名单、DNS 空）（`S3.10`）
- [x] **A5** 设置页 HTTP 200、接入官方后台框架、保存 302 且值落库（`S3.6`/`S3.7`/`S3.8`/`S4.1`）

### 8.2 重复安装（幂等）
- [x] **B1** 自定义值不被默认值覆盖（`S3.13`）
- [x] **B2** 配置行数不膨胀（仍 7 行）（`S3.14`）

### 8.3 停用（历史 Bug 点，最重要回归项）
- [x] **C1** 配置**完整保留**（停用前后逐行相同）（`S5.2`）
- [x] **C2** 前台不再注入（`S5.4`）
- [x] **C3** 设置页被 `CheckPlugin` 拦截、不再渲染表单（`S5.5`）
- [x] **C4** 停用过程无报错，且不越界改动其它配置（`S5.3`）

### 8.4 重新启用
- [x] **D1** 配置沿用（`S5.7`）
- [x] **D2** 前台恢复注入且参数正确（`S5.8`）
- [x] **D3** 设置页回显值 = 停用前值（`S5.9`）

### 8.5 版本升级（覆盖安装）
- [x] **E1** 旧版本配置完整继承（`S6.8`）
- [x] **E2** 缺失配置键被补齐、既有值不被破坏（`S6.11`）
- [x] **E3** 版本标识同步更新（`S6.10`）
- [x] **E4** 落地文件与包内清单严格相等、无旧版残留（`S6.7`）
- [x] **E5** 无 `.git` / `screenshots` 等不应出现的目录（`S1.3`/`S1.4`）
- [x] **E6** 实测记录：**1.0.9 → 1.1.1** 已通过（`S6` 全节）；更早版本链未随本次复跑，见 §10

### 8.6 卸载（删除应用）
- [x] **F1** 插件目录被移除（`S7.2`）
- [x] **F2** 配置保留（有意设计，重装自动沿用）（`S7.3`/`S7.8`）
- [x] **F3** 无残留数据表 / 文件（不建表、不写文件）（`D.1`/`D.3`）
- [x] **F4** 彻底清除配置的入口已提供：后台「高级 → 清除插件配置」（二次确认，只删本插件配置）（`S4.17`/`S4.18`）

### 8.7 debug 模式
- [x] 前台首页 / 前台文章页 / 后台首页 / 插件管理页 / 插件设置页 / 登录页 / 保存配置 / 清除配置：八类报错零命中（`S9.1`–`S9.8`）

## 9. 应用中心上架自检（逐条，标注本次实测依据）

### A. 通用
| 标准 | 本次依据 | 结果 |
|---|---|---|
| 开启 debug 模式后不得报错 | `S9` 9/9 | ✅ |
| 不得含木马等有害代码 | `E.5` 无 `eval` / `base64_decode` / `assert`；源码全明文可读 | ✅ |
| 不得含被加密的 PHP | 全明文提交，无 Z5 或任何加密 | ✅ |
| PHP 文件须 UTF-8 无 BOM | `E.3` | ✅ |
| 不得有安全漏洞（SQL 注入 / XSS / CSRF） | `S4.4`/`S4.5`/`S4.11`/`S4.12`/`S4.16`/`S4.19`；不拼接 SQL（`D.1`） | ✅ |
| **不得引用外站资源** | `D.7` 零命中；`D.5` 第三方 JS 为本地文件 | ✅ |
| 不得跳过应用中心支付系统搞内置收费 | 完全免费，无任何付费校验或外部接口 | ✅ |
| 不得修改系统源码或默认语言包 | `C.1` 四目录逐字节一致 | ✅ |

### C. 插件专项
| 标准 | 本次依据 | 结果 |
|---|---|---|
| 数据库表与 Class 使用 zbp 标准规范 | `D.1` 不建表、不自建 Class | ✅ |
| 数据库操作走系统链式对象，不自行拼接 SQL | `D.1`；仅用官方 `$zbp->Config()` / `SaveConfig()` / `DelConfig()` | ✅ |
| 应用 `$zbp->CheckRights` 判权限，而非裸 `User->Level` | 源码仅 `CheckRights('admin')`，无裸 `User->Level` 判定 | ✅ |
| 使用 `zbignore.txt` 排除打包文件 | `S1.3`/`S1.4` 包内清单与排除结果完全一致 | ✅ |

### D. 应用发布内容
| 标准 | 状态 |
|---|---|
| 后台配置页截图 | 需在发布前补/更新为 1.1.1 界面（`screenshots/` 不进分包） |
| 前台展示截图 | 同上 |
| 基本 / 详细使用说明 | ✅ `plugin.xml` `<description>` + `README.md`（含功能、安装、生命周期、安全） |

## 10. 未随本次复跑的历史结论（不得直接引用）

以下为 1.0.x 版清单中出现、但**本次未重新执行**的项目。如需在发布说明或对外材料中引用，必须先重跑：

| 项目 | 未复跑原因 |
|---|---|
| PHP **7.3.4** 的 `php -l` | 本次本机仅有 PHP 8.2.33；`<phpver>7.4</phpver>` 的依据改为「8.2.33 通过 + 无 PHP 8.0+ 语法」 |
| 远程测试站 `zblog.xmm.fan` 的**主题矩阵** | 该站 `default` / `Zit` / `os2020` 缺模板编译缓存（错误码 86），无法复跑主题矩阵；其余项已在 **§6.3** 复跑（PHP 8.3.33 + MySQL 5.7） |
| `node --check`（3 个 JS 文件） | 本次未执行 JS 语法检查 |
| 前台脚本**功能级**测试（22 项，`test_ps_js_units.py`） | 该脚本不在本仓库，本次未执行 |
| 界面回归 / 布局对齐实测（1440×900） | 本次未执行 |
| 1.0.2 → 1.0.3 → 1.0.4 → 1.0.5 → 1.0.7 的历史升级链 | 本次仅复跑 **1.0.9 → 1.1.1** |
| `_check_zba_struct.py` / `_verify_final_zba.py` / `_scan_security.py` / `audit_pagespeed.py` / `test_zba_install_clean.py` / `build_zba.php` 等脚本 | 上述脚本均不在本仓库（`scripts/` 内仅 `market-audit.py`），本次未执行；本次改以 `run_tests.py` / `run_tests_lifecycle.py` / `run_static_checks.py` 复跑 |
| 「媒体库 上传/改名/删除」「两个插件各一轮停用」等用例 | 属另一插件（`at8_media_library`）的清单内容，1.0.9 版清单误留，本次已移除 |
| 自动审核对内置 instant.page 的黄色提示 | 需在应用中心提交后以实际审核结果为准 |
| `docs/` 下 7 份审计报告（`MARKET_AUDIT_REPORT.md` / `PACKAGE_AUDIT_REPORT.md` / `SECURITY_AUDIT_REPORT.md` / `LIFECYCLE_AUDIT_REPORT.md` / `TEST_MATRIX.md` / `THIRD_PARTY_LICENSE_REPORT.md` / `hook-audit.md`） | 均针对 **1.1.0** 构建生成，本次未重新生成；其中 2 份已在文首加注「当前发布物为 1.1.1」及本版体积/MD5，正文断言未复跑 |

## 11. 复现方式

测试脚本位于本机工作区 `test/`（不进仓库、不进分包）：

| 脚本 | 覆盖 | 本次结果 |
|---|---|---|
| `run_tests.py` | S0–S3：环境基线 / 官方打包 / 干净状态 / 官方安装链路 | **41/41 PASS**（`results_s0_s3.json`） |
| `run_tests_lifecycle.py` | S4–S9：设置页与边界 / 停用重启用 / 覆盖升级 / 卸载重装 / `adapted` 门槛 / debug 零报错 | **68/68 PASS**（`results_lifecycle.json`） |
| `run_static_checks.py` | A–E：元数据一致性 / 官方接口存在性 / 核心目录逐字节对比 / 无自建重复系统 / 代码质量 | **59/59 PASS**（`results_static.json`） |
| `pack_release.py` | R0–R5：官方打包 / `<verify>` 正式站指纹 / 结构元数据 / 清单与逐字节 / 与基线包差分 / 落盘 | **24/24 PASS** |
| `verify_release.py` | V0–V8：用最终发布物走官方安装链路（上传 / 启用 / 前台 / 设置页 / 停用重启用 / 卸载 / 复原） | **23/23 PASS**（`results_release_install.json`） |
| 远程真机（SSH + curl） | R6.3：`zblog.xmm.fan`（PHP 8.3.33 + MySQL 5.7）日志差分零报错 / 功能实测 / 与 `debug` 插件共存 / 对照实验排除主题 500 | **9/9 PASS**（见 §6.3） |
| 合计 | | **224/224 PASS / 0 FAIL** |

环境搭建要点：官方 `zblogphp-1.7.5.3510.zip` 解压为站点根 → SQLite 库 → 后台管理员登录 → 官方 AppCentre 在场（提供 `app_pack` / `app_upload` / `app_del`）。

执行顺序有依赖：`run_tests.py` 会先把站点恢复到「干净 → 官方安装 → 启用」状态，`run_tests_lifecycle.py` 的 S4 依赖该状态，须**按表中顺序**执行。`run_static_checks.py` 为纯文件分析，可并行。

已知环境抖动与处理：测试站为 PHP 内置服务器（`php -S`，单进程、响应带 `Connection: close`）。请求跟随重定向时需新建连接，偶发连不上，此时 `curl -w %{http_code}` 只保留「最后收到的响应码」（302/301）而 `-s` 吞掉错误，会让 `follow=True` 的断言随机变红（本次复跑曾命中 S7.6 / S9.6）。`zt_lib.curl()` 已把「`code=000`」与「`follow=True` 却仍返回 3xx」一并纳入重试条件，消除该抖动；上述两段脚本重跑后均为满通过。

## 12. 发布前必须完成

- [x] ~~重新打包分包~~ —— 已完成，见 §5.3 / §5.4（1.1.1 包已含 `array_key_exists` 修复，逐文件 MD5 与仓库一致）
- [x] ~~在目标站点执行官方打包并记录体积与 MD5~~ —— 已完成，`<verify>` 为正式站指纹 `https://www.at8.fun/`；体积 51594 B / MD5 `51bf0d348855ae896c7a3b166824fc95`
- [x] ~~用该发布物本身走官方安装链路验收~~ —— 已完成，23/23 PASS（§5.4）
- [x] ~~清理 `dist/` 内 1.1.0 缺陷包~~ —— 已移除（git 历史 `8e5c105` 可取回），`dist/` 仅保留 1.1.1 发布物
- [x] ~~远程真机以 1.1.1 复跑~~ —— 已完成，9/9 PASS（§6.3）
- [ ] 更新 `screenshots/` 为 1.1.1 实际界面（现有三张截图为 1.1.0 界面；功能与布局未变，仅 `03-前台注入优化代码.png` 图中文字标注为 `v1.1.0`）
- [ ] 如需在对外材料引用 §10 中的历史结论，先重跑对应脚本

---

**结论**：1.1.1 在 **Z-BlogPHP 1.7.5 Build 3510 + PHP 8.2.33 + SQLite**（本地）与 **Z-BlogPHP 1.7.5 Build 3540 + PHP 8.3.33 + MySQL 5.7 + nginx**（远程真机 `zblog.xmm.fan`）两套环境下，**224 项实测断言全部通过 / 0 FAIL**；未修改任何核心文件、无新增非官方 Hook、无自建重复业务系统、无未经许可的第三方代码。发布物 `dist/at8_pagespeed_1.1.1_20261013.zba`（51594 B / MD5 `51bf0d348855ae896c7a3b166824fc95`）已按官方链路打包并**用该产物本身**通过安装链路验收（§5.4），**无发布阻断项**。

> 本版是 **1.1.0 的重发**：1.1.0 的 `.zba` 为修复前构建（`include.php` 仍用 `isset`），在官方默认主题下前台完全不注入，已由本版取代。
