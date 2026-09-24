# at8_pagespeed 1.0.7 — 发布检查清单（RELEASE_CHECKLIST）

检查基准：《Z-BlogPHP 插件 AI Agent 开发规范》§29 + `zblog审核.md`（上架审核专项清单）
实测环境：Z-BlogPHP **1.7.5** / PHP 7.3.4（本地 `php -l`）+ PHP 8.3.33（测试站 zblog.xmm.fan）/ MySQL
检查日期：2026-09-24
版本：1.0.7（`plugin.xml` / `AT8_PAGESPEED_VERSION` / 本文件 三处一致）

## 1. 元数据与标识

| 项 | 值 | 结果 |
|---|---|---|
| 插件 ID | `at8_pagespeed` | ✅ 长期稳定，未随重构改名 |
| 插件名称 | 页面加速 | ✅ |
| 版本号 | 1.0.7（`plugin.xml` 与 `AT8_PAGESPEED_VERSION` 一致） | ✅ 十进制封十进一 |
| 目录 / 文件前缀 | 函数 `at8_pagespeed_*`、CSS `.ps-*`、JS `window.at8Ps*`、配置键 `conf_Name=at8_pagespeed` | ✅ 无通用命名 |
| `plugin.xml` 必填节点 | id/name/url/note/description/path/include/level/author/source/adapted/version/pubdate/modified/price/**phpver**/advanced | ✅ 齐全 |
| 作者与官网 | 漫步白月光 / https://www.at8.fun/ | ✅ |
| `<source>` 来源名称 | 漫步白月光（**1.0.3 修正**：原为模板遗留值，与 `<author>` 不一致） | ✅ 已一致 |

## 2. 最低系统与 PHP 要求

| 项 | 值 | 依据 |
|---|---|---|
| 最低 Z-BlogPHP | 1.7.x | 依赖 `Filter_Plugin_Zbp_MakeTemplatetags`、`CheckIsRefererValid`、`$zbp->ismanage`、`Config()` 单参属性式 |
| 最低 PHP | **7.4**（`plugin.xml` 显式声明；打包脚本读取，不再写死 5.2） | ① 全量文件 PHP **7.3.4** 通过 `php -l`（严于 7.4）；② 运行时 PHP **8.3.33** 实测零报错；③ 无 PHP 8.0+ 专有语法（已全量扫描 `?->` / `match` 表达式 / `str_contains` / `#[Attribute]` / 构造器提升 / 联合类型 / `enum` / `readonly` 均 0 命中） |
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
| 安装 | 幂等：仅补齐缺失配置键，不覆盖用户已保存值；随后 `at8_pagespeed_migrate_config()` 把 `ConfigVer` 升到当前版本 | ✅ 重复安装后自定义值保留，`ConfigVer` 写入 |
| 启用 | 仅注册输出过滤器 + 后台菜单，无其他副作用 | ✅ |
| 正常运行 | 前台注入（header 预取 / footer 脚本）；后台、登录页、接口不注入 | ✅ 39 项断言全过 |
| 停用 | **不丢配置**（1.7.5 停用会触发 `UninstallPlugin` 钩子） | ✅ Bug 已复现并修复，见下 |
| 重新启用 | 配置沿用，前台立即恢复注入 | ✅ delay=180 + 黑名单全部保留 |
| 升级 | `at8_pagespeed_migrate_config()` 按 `ConfigVer` 逐级迁移；由 `InstallPlugin_` / `UpdatePlugin_` / 设置页加载**三处**调用（覆盖手动覆盖文件升级） | ✅ 1 → 2 分支就位（补 `?act=` / `&act=`，只做加法） |
| 卸载 | 本插件不建表不写文件；配置保留为站点级偏好 | ✅ 已在代码注释说明取舍理由 |

### 本次修复的数据丢失问题（已复现 + 已修复）

1.7.5 的 `DisablePlugin()` 内部会调用 `UninstallPlugin_<id>()`，即**「停用」与「卸载」共用同一钩子**；真卸载（AppCentre 删应用）反而只删目录、不触发该钩子。
1.0.0 在该钩子里调用 `DelConfig()` → 每次停用清空全部设置。实测证据：

```
[复现] 1.0.0 include.php：停用前 delay=180、配置行 7 → 停用后 delay=''、配置行 0
[修复] 1.0.1 include.php：停用前 delay=180 → 停用后 delay=180、配置行 7 → 重启用后仍 180、前台生效
```

### 本次修复的预加载黑名单漏拦（1.0.7，对应上架审核清单 §七「默认策略」）

§七 要求 `登录 / 退出 / 后台 / 删除 / 编辑 / 支付 / 购物车 / 订单 / 评论操作 / Feed` **不得被预加载**。
原默认黑名单（14 项，自 1.0.0 未变）实测只满足 **7 / 10**：

```
[漏拦] cmd.php?act=ArticleDel&id=1&csrfToken=…   ← 删除
[漏拦] cmd.php?act=ArticleEdt&id=1               ← 编辑
[漏拦] cmd.php?act=CommentDel&id=1               ← 评论操作
```

根因：清单里是 `delete` / `remove` / `edit` 等通用英文词，而 Z-Blog 敏感操作全部走
`cmd.php?act=XxxDel` / `XxxEdt` / `XxxSav` 命名，**清单中没有任何一项能匹配**。

数据丢失链路（逐环源码取证，非推测）：

```
instantpage.js  _delayOnHover=65 + mouseover → setTimeout(65ms)      悬停 65ms 即预取，无需点击
instantpage.js  preloadUsingLinkElement() → <link rel=prefetch as=document>
浏览器          发出真实同源 GET（自带 Cookie 与 Referer）
cmd.php         $action = GetVars('act','GET') → case 'ArticleDel': CheckIsRefererValid(); DelArticle();
c_system_common CheckCSRFTokenValid($field='csrfToken', $methods=array('get','post'))  显式接受 GET 的 token
c_system_common CheckHTTPRefererValid()：referer 为空直接 return true                 无 Referer 也放行
```

修复：

```
[修复] 1.0.7 defaults：新增 ?act= / &act=，移除 wp-admin / admin_ / ?t=（14 项 → 13 项）
[修复] 1.0.7 输出层不变量：at8_pagespeed_blacklist_effective() 注入前始终合并 ?act= / &act=
[修复] 1.0.7 migrate：ConfigVer 1 → 2，等值旧默认则整条替换，已自定义则仅追加缺失项
        调用点三处：InstallPlugin_ / UpdatePlugin_ / main.php（设置页加载）
[实测] 测试站登录管理员抓前台 <a href>：ArticleEdt / ArticleDel / PageEdt / PageDel 全部由 ❌ 未拦 → ✅ 已拦
```

> **为什么必须有「输出层不变量」这一层**：Z-BlogPHP 核心并没有 `UpdatePlugin()` 函数
> （核对 1.7.5 源码，`c_system_plugin.php` 仅 `InstallPlugin` / `UninstallPlugin`）。
> 官方升级钩子实际由 `c_system_misc.php` 的 `misc&type=updatedapp` 路由触发，而该路由是后台
> 页面里 `<script src>` 带出来的 —— 迁移能否执行取决于「管理员进后台 + 浏览器执行脚本」。
> **手动覆盖文件升级时两个钩子都不会触发**，配置迁移不会跑。因此安全项不能只依赖配置迁移。
> 实测记录：本轮部署时 AppCentre 上传后立即查库，`ConfigVer` 仍为 1、黑名单仍是旧值
> —— 正是这条链路依赖的实证；补上输出层兜底后，不迁移的站点前台依然被正确拦截。

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
- 安装回归（1.0.4）：**25 项断言 25 PASS / 0 FAIL**（干净包经官方「应用中心 → 上传应用」链路实装；落地清单与包内清单严格相等；无 `.git` 残留；额外落地文件仅 `cache/`，属运行时缓存）
- **升级回归（1.0.2 → 1.0.3 → 1.0.4，官方上传应用链路覆盖安装）**：版本常量与 `plugin.xml` 同步 ✅ / 配置 7 项全部保留 ✅ / 9 个落地文件与包内清单一致 ✅
- **功能回归（1.0.4）**：**39 项断言 39 PASS / 0 FAIL** —— 前台注入、后台不注入、登录页不注入、设置页渲染、CSRF 合法/篡改两组、重复安装幂等、debug 模式 5 页零报错、停用不丢配置（★）、重启用沿用
- **前台脚本功能级测试（1.0.4，真实浏览器构造 DOM）**：**22 项断言 22 PASS / 0 FAIL** —— 黑名单大小写/URL 编码绕过拦截、正常链接不误伤、容器级与嵌套排除、`data-no-lazy` 不影响预加载、首屏跳过计数、已有 `loading` / `fetchpriority` 不被改写、`display:none` 不处理、iframe 补属性、动态插入兜底
- **打包结构校验（1.0.4）**：**26 项 26 PASS** —— gzip 魔数、根节点 `version="php"` + `type="plugin"`、`<id>` 等于目录名、`<folder><path>` 与 `<file><path>+<stream>` 结构完整、路径均以 `<id>/` 开头且无 `./`、内容逐字节一致
- **安全扫描（1.0.4）**：**需人工复核项 0**；22 处规则命中已逐条复核并白名单化（上游库注释链接、缓存写入、附件删除等），BOM 0 处、`!important` 0 处
- **前台注入实测（1.0.4）**：`<head>` 注入 `<!-- at8_pagespeed 1.0.4 -->` + `x-dns-prefetch-control` + `link[rel=dns-prefetch]`；`</body>` 前注入 `at8PsLazySkip` / `at8-lazy.js` / 内联写 `data-instant-intensity` / `at8PsBlacklist`+`at8PsPreloadDelay` / `at8-guard.js` / `instantpage.js` —— 与代码预期逐项一致
- **debug 模式实测**：页面内 `Fatal error` / `Parse error` / `Warning` / `Deprecated` / `Notice` / `Uncaught` 关键字**零命中** ✅
- 界面回归（1.0.2）：**17 项断言 17 PASS / 0 FAIL** —— 设置页已接入官方后台框架，`admin2.css` / `zblogphp.js` 已加载、顶栏 `#topmenu` 与左侧菜单 14 项（含 `nav_at8_pagespeed`）正常渲染、仅 1 个 doctype、无 PHP 告警、保存往返正常
- 布局对齐实测（1440×900）：`#divMain` 宽 1280 / 起点 x=150 / `max-width:none`、内容容器内距 `20px 24px 60px` / `max-width:1400px` —— 与 `at8_media_library` 设置页逐项一致
- 回归复现脚本：`audit_pagespeed.py`、`audit_repro_disable.py`、`test_zba_install_clean.py`、`deploy_ps_102.py`、`shot_release_ps.py`（截图）

## 7.1 模拟用户全流程 + debug 模式（`test_user_flow_debug.py`，**52 项 52 PASS**）

全程开启 `ZC_DEBUG_MODE=true`，按真实用户使用顺序走一遍，每步都扫
`Fatal / Parse / Warning / Notice / Deprecated / Strict Standards / Uncaught / Undefined` 八类报错。

| 阶段 | 操作 | 结果 |
|---|---|---|
| 登录 | 登录页 → 提交凭据 → 进后台首页 | ✅ 零报错 |
| 配置（正常值） | 打开设置页 → 改 6 项 → 保存 | ✅ 302 回本页、值全部落库 |
| 配置（边界值） | 延迟填 `0`、跳过填 `0` | ✅ 接受（最激进档） |
| 配置（超上限） | 延迟填 `99999`、跳过填 `999` | ✅ 被夹紧为 `2000` / `20`，未写入脏值 |
| 配置（恶意输入） | DNS 填 `not a domain` / `http://evil.com/x` / `<script>alert(1)</script>` / `good.example.com` | ✅ 库里只剩 `evil.com` + `good.example.com`；前台 DNS 链接全部合法、无恶意串 |
| 关闭全部开关 | 两个功能都关 → 前台验证 | ✅ 不再注入任何脚本 |
| 媒体库 | 上传中文名 PNG → 改名 → 删除 | ✅ 记录 +1 → 改名无报错 → 删除后回到初始 |
| 媒体库（非法输入） | `act=evil<script>`、`id=1 OR 1=1` | ✅ 400 拒绝且不回显 / 不误删数据 |
| 停用 → 重启用 | 两个插件各一轮 | ✅ ★停用不丢配置（7→7）、不丢附件记录；重启用后自定义值 180 沿用 |
| debug 开关 | 结束时还原 `false` | ✅ |

> 附带反向验证：`?act=Admin`（大写 A）会被拦回登录页 —— Z-Blog 的 actions 表全小写，
> 这是本插件早期踩过的坑，已固化为断言。

## 7.2 官方《注意事项速查表》逐条核对（桌面 `zblog开发注意事项`）

| 条目 | 核对结果 |
|---|---|
| 开发模式下不报错 | ✅ 见 7.1，八类报错零命中 |
| 影响数据/文件的操作须加 CSRF Token | ✅ 设置页 `CheckIsRefererValid()`；实测篡改 token 被拦（HTTP 500）且配置未变 |
| 函数名以应用 ID 开头 | ✅ 全部 `at8_pagespeed_*` |
| 自建表 / 模块命名 `plugin_appID_*` | ✅ N/A（不建表、不建模块） |
| 站内链接须用绝对地址（`$zbp->host` / bloghost） | ✅ 菜单、资源、API 全部 `$zbp->host` 拼接 |
| 服务端网络请求用自带 Network | ✅ N/A（无任何出站请求） |
| 用 `zbignore.txt` 排除打包文件 | ✅ 已排除 README / CHANGELOG / RELEASE_CHECKLIST / screenshots / `cache` / `.git` |
| 不自带 jQuery | ✅ 媒体库用原生 `XMLHttpRequest`；本插件无 JS 框架依赖 |
| 编辑器通用性 | ✅ N/A（不涉及编辑器） |
| 主题模板 HTML 在当前文件内闭合 | ✅ N/A（插件；后台页走官方框架 `admin_header/top/footer`） |
| 定制字体图标而非引入整套 | ✅ 无外部图标库 |
| 不写死与自己强关联的东西 | ✅ 无硬编码站点域名 / 路径 |
| CSS、JS 走外部引用，不用 `style=""` | ✅ CSS/JS 均外部文件；`style=""` 0 处（仅测试夹具里有） |
| **放弃 `var`，改用 `let` / `const`** | ✅ **1.0.5 修正**：两个自研脚本原用 `var`（共 23 处），已全部改为 `let`/`const` |
| `link:css` / `script:js` 弃用非必要属性 | ✅ 无 `type="text/javascript"`；`defer` 为功能必需保留 |
| 正则用否定匹配而非 `.*?` | ✅ `.*?` 0 处（已扫描） |
| logo 等替代性文件不走附件机制 + zbignore 防覆盖 | ✅ `logo.png` 随包发布，`zbignore` 未排除（需随包）但不走附件机制 |
| 保存配置用 `SetHint` + `Redirect` 而非 `ShowHint` | ✅ `main.php` 用 `$zbp->SetHint('good', ...)` + `Redirect('./main.php')` |
| 有限度使用 Heredoc | ✅ 未使用 |

## 8. 安装 / 停用 / 卸载 / 升级测试清单

可勾选的验收项。**判定标准**列即通过条件，不满足即为不通过，不允许「大致正常」。
命令中的 `zbp_config` 记录数用：`SELECT COUNT(*) FROM zbp_config WHERE conf_Name='at8_pagespeed';`

### 8.1 全新安装（干净站点，从未装过）

- [x] **A1** 应用中心上传 `zba` → 安装成功，无报错、无 `ShowError`
  - 判定：插件目录出现且文件数 = 包内文件数（当前 **10**）
- [x] **A2** 插件列表启用 → 成功，前台立即生效
  - 判定：前台源码出现 `<!-- at8_pagespeed <版本> -->` 注释
- [x] **A3** 配置初始化正确
  - 判定：`zbp_config` 中 `conf_Name='at8_pagespeed'` 记录数 ≥ 7，且 `ConfigVer` 存在
- [x] **A4** 默认值符合预期
  - 判定：预加载开、懒加载开、DNS 预取开、延迟 65ms、跳过首屏 2 张
- [x] **A5** 后台设置页可正常打开与保存
  - 判定：HTTP 200，左侧菜单 14 项含 `nav_at8_pagespeed`，保存后 302 回设置页且值落库

### 8.2 重复安装（幂等性）

- [x] **B1** 改动若干配置 → 再次上传安装同一个包
  - 判定：用户自定义值**不变**（不被默认值覆盖）
- [x] **B2** 记录数不重复膨胀
  - 判定：记录数仍为 7，不出现重复键

### 8.3 停用（关键：历史 Bug 点）

- [x] **C1** 停用后配置**完整保留**
  - 判定：停用前后 `SELECT conf_Key,conf_Value ...` 结果**逐行相同**
- [x] **C2** 停用后前台不再注入
  - 判定：前台源码中 `at8_pagespeed` 字样 0 命中
- [x] **C3** 停用后后台菜单消失
  - 判定：左侧菜单无 `nav_at8_pagespeed`
- [x] **C4** 停用过程无报错
  - 判定：无 `Fatal` / `Warning` / `Notice`

> 说明：1.7.5 `DisablePlugin()` 内部调用 `UninstallPlugin_<id>()`，停用与卸载共用钩子。
> 因此 **C1 是本插件最重要的回归项**——一旦失败即为用户数据丢失（1.0.0 曾发生，已修复）。

### 8.4 重新启用

- [x] **D1** 启用后配置沿用
  - 判定：自定义值与停用前一致（如自定义 `preload_delay=180` 仍为 180）
- [x] **D2** 前台恢复注入且参数正确
  - 判定：注入片段含 `data-instant-intensity="180"` 等自定义值
- [x] **D3** 无需重新配置即可工作
  - 判定：设置页显示值 = 停用前的值

### 8.5 版本升级（覆盖安装，不删目录）

- [x] **E1** 旧版本配置完整继承
  - 判定：升级前后配置键集合相同、自定义值不变
- [x] **E2** 新增配置键被补齐
  - 判定：新版本新增的键出现在库中，且为默认值（不破坏已有值）
- [x] **E3** 版本标识同步更新
  - 判定：`include.php` 常量与 `plugin.xml` 的 `<version>` 均为新版本
- [x] **E4** 落地文件与包内清单严格相等
  - 判定：目录内文件数 = 包内文件数，无旧版本残留文件
- [x] **E5** 升级后无 `.git` / `cache` / `screenshots` 等不应出现的目录
  - 判定：`find` 无命中（`cache/` 为运行时目录属例外，见 8.6）
- [x] **E6** 实测记录：1.0.2 → 1.0.3 → 1.0.4 → 1.0.5 均通过（1.0.6 仅调整 JS 注入转义标志与文档表述，未变更生命周期逻辑，沿用 1.0.5 结论）；**1.0.7 新增 `ConfigVer` 1 → 2 迁移分支，已在测试站覆盖安装实测**（见 8.5 之 1.0.7 小节）

### 8.6 卸载（删除应用）

- [x] **F1** 删除应用后插件目录被移除
  - 判定：目录不存在
- [x] **F2** 配置**保留**（有意设计，非残留 Bug）
  - 判定：`zbp_config` 中仍有 `conf_Name='at8_pagespeed'` 记录，重装后自动沿用
- [x] **F3** 无残留数据表 / 文件
  - 判定：不建表；`zb_users/upload/` 等其他目录无本插件写入物
  - 例外：`zb_users/plugin/at8_pagespeed/cache/` 为运行时缓存，随目录一并删除
- [x] **F4** 彻底清除配置的手动方式已文档化
  - 判定：README「生命周期与数据」写明 `DELETE FROM zbp_config WHERE conf_Name='at8_pagespeed';`

### 8.7 自动化回归脚本对照

| 脚本 | 覆盖项 | 当前结果 |
|---|---|---|
| `test_user_flow_debug.py` | **模拟用户全流程 + debug**：登录 / 配置 / 边界值 / 恶意输入 / 传文件 / 停用 / 启用 | **52 PASS / 0 FAIL** |
| `test_zba_install_clean.py` | A1 / A3 / A4 / E1 / E3 / E4 / E5（官方上传应用链路） | 25 PASS / 0 FAIL |
| `audit_repro_disable.py` | C1 / D1 / D2（数据丢失 Bug 复现与修复验证） | PASS |
| `audit_pagespeed.py` | A2 / A5 / C2 / C3 / D2 + 安全与权限 | 39 PASS / 0 FAIL |
| `test_ps_js_units.py` | 前台脚本功能级（黑名单 / 懒加载 / 容器级排除 / 动态兜底） | 22 PASS / 0 FAIL |
| `_check_zba_struct.py` | 打包结构（gzip 魔数 / 根属性 / folder+file 节点 / 路径规范） | 26 PASS / 0 FAIL |
| `_scan_security.py` | 危险函数 / 外站资源 / BOM / `!important` | 需复核项 0 |

## 9. 发布物

- `at8_pagespeed_1.0.7_20260924.zba`（**42.5 KB / 43513 B，10 文件**：插件文件 + `LICENSE` + `README.md`，已剔除 CHANGELOG / RELEASE_CHECKLIST / `screenshots` / zbignore / `cache` / `.git`）
  - 校验：`_check_zba_struct.py` **26/26 PASS**、`_verify_final_zba.py` 反向逐字节比对一致 + 排除/保留核验全 PASS
  > ⚠️ 打包污染教训：本插件目录内就是 git 工作区，早期打包脚本只按 `zbignore.txt` 排除，
  > 导致 `.git` 整棵树（35 个文件、约 51 KB）被打进分包。现已在 `build_zba.php` 加入
  > 「不依赖 zbignore 的强制排除清单」，并对包内文件做逐文件 MD5 校验（`_verify_zba.py`）。
- 上架截图 `screenshots/`（不进分包，仅供应用中心上传）：`01-后台设置页.png`（1600×1310）、
  `02-前台页面-加速生效.png`（1600×1000）、`03-前台注入优化代码.png`（1600×542）
- GitHub：https://github.com/361611074/at8_pagespeed

## 10. Z-Blog 应用中心上架自检（对官方《发布应用》标准逐条）

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
