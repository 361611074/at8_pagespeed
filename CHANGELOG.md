# 更新日志 · at8_pagespeed

版本号规则：十进制封十进一（每段 0~9，满 10 进位），不用 1.2.10 这类写法。

## 1.0.8（2026-09-24）

**修正适配版本声明（`<adapted>`），无功能变更**

- `<adapted>` 由 `172900` 改为 **`173510`**，即声明最低支持 **Z-BlogPHP 1.7.5 Build 3510**。
  原值 `172900` 相当于 1.7.2 系列，门槛明显偏低，与「实际只面向 1.7.5 验证」不符。

- **编码规则（源码实锤）**：核心 `zb_system/function/lib/app.php` 的 `CheckCompatibility()`：

  ```php
  if ((int) $this->adapted > (int) $zbp->version) {
      return new Exception(str_replace('%s', $this->adapted, $zbp->lang['error'][78]));
  }
  ```

  是**整数比较**，超过即拒绝安装 —— 所以 `<adapted>` 声明的是**最低**版本，不是「开发所用版本」
  （应用中心后台的字段名即「适配的Z-Blog最低版本」）。

  `$zbp->version` 取值来自 `zb_system/function/c_system_version.php`：

  ```php
  $GLOBALS['blogversion'] = ZC_VERSION_MAJOR . ZC_VERSION_MINOR . ZC_VERSION_COMMIT;
  ```

  **注意 `ZC_VERSION_BUILD` 不参与拼接**。以 1.7.5 为例，`MAJOR=1` / `MINOR=7` / `BUILD=5` /
  `COMMIT=3510`，故 `blogversion = '1' . '7' . '3510' = '173510'`。

  | 版本 | 拼接结果 | `<adapted>` 取值 |
  |---|---|---|
  | 1.7.5.3510 | `173510` | **173510（本次采用）** |
  | 1.7.5.3540（测试站实测） | `173540` | — |
  | 1.7.2.2900（旧值含义） | `172900` | 旧值 |

- **效果**：1.7.4 及更早版本（`$zbp->version` < `173510`）会在安装阶段被核心拦下并提示版本不满足；
  1.7.5 Build 3510 及以上正常安装。测试站（1.7.5.3540，`$zbp->version = 173540`）验证 `173510 <= 173540` 成立，安装不受影响。

- 同步更新 `include.php` 头部注释、README「兼容性」表与 `RELEASE_CHECKLIST.md`。

## 1.0.7（2026-09-24）

**修复预加载黑名单漏拦 Z-Blog 系统操作导致的数据丢失风险（安全修复，建议所有站点升级）**

- **问题（实测可复现）**：默认黑名单用的是 `delete` / `remove` / `edit` 等通用英文词，
  而 Z-Blog 的敏感操作全部走 `cmd.php?act=ArticleDel` / `ArticleEdt` / `CommentDel` /
  `TagDel` / `MemberDel` / `UploadDel` / `ModuleDel` / `CategoryDel` / `SettingSav` 命名，
  **清单里没有任何一项能匹配到**。在测试站（Z-BlogPHP 1.7.5）登录管理员浏览前台，
  页面上的 `cmd.php?act=ArticleDel&id=1&csrfToken=…`、`act=ArticleEdt`、`act=PageDel`
  逐条比对后确认**全部未被拦截**。
- **后果**：`instant.page` 悬停 `_delayOnHover`（默认 65ms）即注入
  `<link rel=prefetch as=document>`，浏览器会发出**真实同源 GET**（自带 Cookie 与 Referer）；
  而 `zb_system/cmd.php` 中 `$action = GetVars('act','GET')` → `case 'ArticleDel':
  CheckIsRefererValid(); DelArticle();`，且 `CheckIsRefererValid()` 内部
  `CheckCSRFTokenValid($fieldName = 'csrfToken', $methods = array('get','post'))`
  **显式接受 GET 来源的 token**、`CheckHTTPRefererValid()` 在 Referer 为空时**直接 return true**
  —— 两条路径都放行。即：**管理员在前台悬停「删除」链接 65 毫秒，文章即被删除，全程无需点击。**
- **修复**：默认黑名单新增 `?act=` 与 `&act=` 两个精确锚点，覆盖全部 `cmd.php?act=*` 操作，
  一举补齐「删除 / 编辑 / 评论操作」三类缺口（对应上架审核清单 §七 默认策略的必拦项）。
  不用裸 `act=` 是为了避开 `?contact=1` 这类无关参数误伤。
- **清理 WordPress / jQuery 遗留项**：移除 `wp-admin` 与 `admin_`（前者含子串 `admin`、
  后者同理，**都被 `admin` 完全包含，属永不生效的死项**）、`?t=`（jQuery 的缓存击穿参数，
  前台 `<a href>` 实测零命中；jQuery 内部的 `?t=` 只出现在 `.js` 库文件中，不在页面链接上）。
  默认清单由 14 项变为 13 项。
- **安全项在输出层兜底（关键设计）**：Z-BlogPHP 核心**并没有 `UpdatePlugin()` 函数**——
  核对 1.7.5 全量源码，`zb_system/function/c_system_plugin.php` 里只有 `InstallPlugin()` 与
  `UninstallPlugin()`；官方文档所说的「更新插件时执行」实际由 `c_system_misc.php` 的
  `misc&type=updatedapp` 路由调用（`$fn = 'UpdatePlugin_' . $appid`），而该路由是后台页面里
  由 `Include_Admin_UpdateAppAfter()` 输出的 `<script src>` 带出来的。
  也就是说「迁移能否执行」取决于**管理员是否进入后台 + 浏览器是否执行了那段脚本**；
  若站点是**手动覆盖文件**升级（Z-Blog 上很常见），两个钩子根本不会触发，迁移永远不会跑。
  因此把 `?act=` / `&act=` 做成**输出层不变量**：`at8_pagespeed_blacklist_effective()` 在注入前
  台前始终合并这两项（O(1) 纯数组追加，不写库、不依赖版本），任何升级路径下都成立。
- **存量站点迁移**：`InstallPlugin_at8_pagespeed()` 原为「仅补齐缺失键、不覆盖已存值」的幂等
  设计，**只改默认值对已安装站点完全无效**。新增 `at8_pagespeed_migrate_config()`
  （`ConfigVer` 1 → 2），由 `InstallPlugin_` / `UpdatePlugin_` / 设置页加载**三处**调用
  （第三处专门覆盖手动覆盖文件升级的场景）：
  1. 黑名单仍是旧默认值（用户从未自定义）→ 整条替换为新默认值；
  2. 用户已自定义 → **仅追加缺失的 `?act=` / `&act=`**，其余条目原样保留。
  迁移**只做加法**，绝不删改用户自己写的关键字。设置页保存时同样会补回被误删的强制项，
  保证「界面显示 = 库里配置 = 实际生效」三者一致。
- **文档修正**：README 中「默认已排除退出登录、后台、购物车、支付、删除、编辑、feed 等」
  与实际不符（删除 / 编辑当时并未被拦），已改为按实际覆盖范围描述并补充「为什么必须拦 `?act=`」
  的说明；`plugin.xml` 的 `<description>` 同步；实测环境由 PHP 8.2 更正为 **8.3.33**
  （`include.php` 头部注释、`plugin.xml` 的 `<phpver>` 依据、README 兼容性表）。

## 1.0.6（2026-09-24）

规范符合性微调（无功能变更）：

- **JS 注入安全加固**：黑名单关键字通过 `json_encode` 内联到 `<script>` 时补 `JSON_HEX_TAG` / `JSON_HEX_AMP` / `JSON_HEX_APOS` / `JSON_HEX_QUOT` 四个 HEX 标志，防管理员配置中含 `</script>` / `<!--` / `"` 等字符时 breakout `<script>` 上下文。功能不变，HTTP 响应中（`Content-Type: application/json`）不受影响。
- **宣传文案去绝对化**：`plugin.xml` 的 `<description>` 中原"减少点击后的等待""以保证首屏大图（LCP）不被拖慢"改为"可能减少点击后的等待""以减小对首屏大图（LCP）渲染时机的影响"，并补充"实际效果取决于浏览器、主题与页面结构，本插件不承诺固定的提速数值"。
- `zbignore.txt` 调整：`README.md` 不再排除（上架审核对使用说明有要求，README 进 zba 包便于审核员查阅）；`CHANGELOG.md` / `RELEASE_CHECKLIST.md` / `screenshots` / `cache` / `.git` 仍按原状排除。
- 同步更新 `include.php` / `plugin.xml` / `README.md` / `RELEASE_CHECKLIST.md` 的版本号。

## 1.0.5（2026-09-23）

- **最低 PHP 版本提高至 7.4**（`plugin.xml` 的 `<phpver>`，应用中心据此拒绝安装）。
  依据（三条，均可复现）：
  1. 全量 PHP 文件在 **PHP 7.3.4** 通过 `php -l`（7.3.4 严于 7.4，向下兼容已覆盖）；
  2. 运行时在 **PHP 8.2** 测试站实测零报错（debug 模式 5 个页面）；
  3. 源码未使用任何 PHP 8.0+ 专有语法——已全量扫描确认无 `?->`、`match` 表达式、
     `str_contains` / `str_starts_with` / `str_ends_with`、`#[Attribute]`、构造器提升、
     联合类型、`enum`、`readonly`、`never`、命名参数。
  原先按「语法够用即放行」声明，现改为只放行做过兼容性承诺的版本区间。
- **DNS 预取域名改为「保存时即归一化」**（纵深防御）：
  原先只在输出到前台时校验，库里存的是用户原始输入（含 `not a domain`、`http://evil.com/x`、
  `<script>alert(1)</script>` 这类脏数据）。现抽出 `at8_pagespeed_normalize_domain()`，
  **保存与输出共用同一套规则**：去协议、去路径、校验域名格式，非法项直接丢弃并去重。
  实测：上述三项输入保存后库里只剩 `evil.com` 与 `good.example.com`，前台 DNS 预取链接全部合法。
- **JS 代码风格对齐官方《注意事项速查表》**：`at8-guard.js` / `at8-lazy.js` 放弃 `var`，
  统一改用 `let` / `const`（规范要求）。功能不变，22 项浏览器端功能级断言仍全过。
  连带影响：这两个脚本现需 ES6 环境（与内置 instant.page 一致），已在 README「浏览器」一节说明。
- 同步更新 `include.php` 头部注释、README「兼容性」与「浏览器」、发布检查清单第 2 节。

## 1.0.4（2026-09-23）

规范复核（两个功能性 Bug + 安全策略加强 + 文档与元数据修正）：

- **修复「触发延迟」设置完全无效（功能性 Bug）**：
  instant.page v5 的悬停延迟读取自 `document.body` 的 `data-instant-intensity`；
  v4 时代是读 `<script>` 标签上的同名属性，v5 已改。原实现把属性写在 `<script>` 标签上，
  **无人读取**，后台无论填多少都始终走内置默认 65ms。
  现改为在 instant.page 执行前用同步内联脚本写入 `document.body`（footer 位于 body 内，此时 body 已存在），
  并由 `at8-guard.js` 再做一次兜底写入。
  源码依据：`assets/instantpage.js` 的 `init()` 中
  `if ('instantIntensity' in document.body.dataset) { ... _delayOnHover = intensityAsInteger }`。

- **预加载黑名单匹配加强**（安全策略）：
  原实现只对 `href` 原文做一次区分大小写的 `indexOf`，存在两类绕过——
  大小写差异（`/Logout` 对 `logout`）与 URL 编码（`log%6Fut`）。
  现改为三重比对：`href` 原文小写、`decodeURIComponent` 后再小写、
  以及浏览器归一化后的 `pathname + search` 小写；任一命中即打 `data-no-instant`。
  `MutationObserver` 兜底同时改为只扫描新增节点，不再每次变更都全文档重扫。

- **新增容器级预加载排除**：
  instant.page v5 只判定 `<a>` 自身的 `dataset.noInstant`（内置文件源码实锤，无祖先查找），
  不支持「在容器上声明一次，整块区域都不预加载」。
  现由 `at8-guard.js` 沿父链查找 `data-no-instant`，命中即为容器内所有链接补标记。
  动态插入的容器同样生效（MutationObserver 已覆盖）。

- **懒加载保守化与性能修正**：
  - 新增「元素带 `fetchpriority` 属性则不动」——作者已给出优先级提示（如 LCP 大图）时以作者意图为准；
  - 新增「未渲染 / `display:none` 元素不动」（`getBoundingClientRect()` 尺寸为 0）：无法判断其视口位置，不猜；
    原先注释声称会跳过此类元素但代码并未实现，属注释与实现不符；
  - 祖先 `data-no-lazy` 判定改为自行沿父链查找，不再依赖 `Element.closest`，兼容面更广；
  - 改为「第一趟只测量、第二趟统一写属性」的两趟处理，消除边读边写触发的多次强制重排。

- **文档修正**：
  - **README 中「卸载会删除本插件配置」与代码实现矛盾**——代码中的卸载钩子是有意空实现、不删除配置。
    该行已按实际行为重写，并补充「停用与卸载共用钩子」的机制说明；
  - README「兼容性」由三行简述重写为正式说明，分「运行环境 / 主题与插件 / 浏览器 / 已知限制」四节；
  - 去除绝对化表述：原「不接管、不修改系统任何业务流程」「对其他插件与站点数据零侵入」
    「任何前台主题」「点击几乎零等待」「进入视口才加载」等，均改为有边界、可验证的表述；
  - 明确写出懒加载的固有限制（脚本补写属性晚于浏览器发起请求、不处理动态插入的图片）
    与「不承诺固定提速数值」。

- **元数据**：
  - `<phpver>` 的依据注释与源码注释对齐（当时声明的 PHP 下限已于 **1.0.5** 提高至 **7.4**）；
  - `<adapted>` 补充取值依据：与 `$zbp->version` 做数值比较（`lib/app.php` 的 `CheckCompatibility()`），
    1.7.x 的 `$zbp->version` 为 `MAJOR.MINOR.COMMIT`（如 1.7.5 Build 3540 → `173540`），
    取 `172900` 表示面向 1.7 系列、可拦住 1.6 及更早版本；
  - `<note>` / `<description>` 同步去除绝对化表述。

## 1.0.3（2026-09-23）

上架准备（元数据与发布物料，无功能变更）：

- **修正 `plugin.xml` 的 `<source>` 来源名称残留**：原值为插件模板遗留的他人标识，与 `<author>` 不一致，
  已改为作者「漫步白月光」——避免审核时被误判为二次改编他人作品；
- 补充应用中心发布物料 `screenshots/`（3 张，宽 1600）：后台设置页、前台页面渲染、前台实际注入的优化代码；
- 回归验证：经官方「上传应用」链路覆盖安装（1.0.2 → 1.0.3）通过，配置 7 项全部保留，
  前台注入标签与预期一致，页面内 PHP 报错关键字零命中。

## 1.0.2（2026-09-23）

界面修复：

- **修复设置页丢失后台整体框架的问题**：`main.php` 原先自行输出 `<!DOCTYPE html>` / `<html>` / `<head>` / `<body>` 整页结构，
  导致页面脱离 Z-BlogPHP 后台框架——**左侧菜单与顶栏全部消失**，与其它后台页面（如媒体库）不一致。
  现改为引入官方框架 `zb_system/admin/admin_header.php` + `admin_top.php` + `admin_footer.php`，并以
  `$blogtitle` 设置页面标题、`<div id="divMain">` 作为内容容器，底部调用 `RunTime()`；
- 移除页面内手写的 `$zbp->GetHint()`（官方 `admin_top.php` 已统一输出，避免提示重复）；
- 样式表 URL 由相对路径改为站点绝对路径，与后台其它插件页面保持一致。

## 1.0.1（2026-09-23）

规范符合性修复，无功能变更：

- **文档/注释纠错**：删除对新版不存在的 Hook（`Filter_Plugin_Zbp_Header` / `Filter_Plugin_Zbp_Footer`）的描述，统一为 1.7.x 实际存在的 `Filter_Plugin_Zbp_MakeTemplatetags`（前台 head/footer 唯一官方注入口）；
- **移除 API 猜测**：`at8_pagespeed_is_frontend()` 原文引用未定义常量 `ZBP_IN_ADMIN`（1.7.5 无此常量），改为 `$zbp->ismanage`（官方原生管理员标记）+ 请求脚本位于 `/zb_system/` 的双重判定；
- **跳转安全**：保存配置后 `Redirect($_SERVER['HTTP_REFERER'])` → `Redirect('./main.php')`，消除开放重定向风险与直接 POST 时 `Undefined array key` 告警；
- **修复「停用即丢配置」的数据丢失问题**：1.7.5 的 `DisablePlugin()` 内部会调用 `UninstallPlugin_xxx()`（停用与卸载共用同一钩子，
  真卸载走 AppCentre 删目录反而不触发），原实现在该钩子里删除插件配置，导致每次停用都丢失全部自定义设置。现改为空实现，
  配置作为站点级偏好保留、重装自动沿用；
- **安装幂等**：`InstallPlugin_at8_pagespeed()` 仅补齐缺失配置键，不再覆盖用户已保存的值；新增 `UpdatePlugin_at8_pagespeed()`（按 `ConfigVer` 迁移）与旧版钩子别名；
- **输出转义**：设置页 `lang` 属性输出经 `htmlspecialchars` 处理；
- **元数据**：`plugin.xml` 补充 `<description>`、显式声明 `<phpver>`（原打包脚本写死 5.2，与实际无关；当前值为 **7.4**，见 1.0.5）；
- 新增 `LICENSE` / `CHANGELOG.md` / `RELEASE_CHECKLIST.md`。

## 1.0.0（2026-09-23）

首个版本：

- 链接悬停预加载：内置 instant.page v5.2.0，延迟可调；
- 敏感链接黑名单（后台可配，动态链接 MutationObserver 兜底）；
- 图片 / iframe 懒加载，可跳过首屏前 N 张（保 LCP），支持 `data-no-lazy` 排除；
- DNS 预取（站点自身 + 后台附加域名，输出端格式白名单校验）。
