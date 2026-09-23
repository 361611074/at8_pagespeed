# 更新日志 · at8_pagespeed

版本号规则：十进制封十进一（每段 0~9，满 10 进位），不用 1.2.10 这类写法。

## 1.0.5（2026-09-23）

- **最低 PHP 版本提高至 7.4**（`plugin.xml` 的 `<phpver>`，应用中心据此拒绝安装）。
  依据（三条，均可复现）：
  1. 全量 PHP 文件在 **PHP 7.3.4** 通过 `php -l`（7.3.4 严于 7.4，向下兼容已覆盖）；
  2. 运行时在 **PHP 8.2** 测试站实测零报错（debug 模式 5 个页面）；
  3. 源码未使用任何 PHP 8.0+ 专有语法——已全量扫描确认无 `?->`、`match` 表达式、
     `str_contains` / `str_starts_with` / `str_ends_with`、`#[Attribute]`、构造器提升、
     联合类型、`enum`、`readonly`、`never`、命名参数。
  原先声明 5.6 属「语法够用即放行」，现改为只放行做过兼容性承诺的版本区间。
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
  - `<phpver>` 的依据注释与源码注释对齐：代码语法与函数最低要求 5.4（`JSON_UNESCAPED_UNICODE`），
    声明下限取 **5.6**——5.4 / 5.5 未做实测故不作兼容承诺（原注释写「仅使用 PHP 5.4+ 通用语法」但声明 5.6，
    理由与取值不一致）；
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
- **元数据**：`plugin.xml` 补充 `<description>`、显式声明 `<phpver>5.6</phpver>`（原打包脚本写死 5.2，与实际无关）；
- 新增 `LICENSE` / `CHANGELOG.md` / `RELEASE_CHECKLIST.md`。

## 1.0.0（2026-09-23）

首个版本：

- 链接悬停预加载：内置 instant.page v5.2.0，延迟可调；
- 敏感链接黑名单（后台可配，动态链接 MutationObserver 兜底）；
- 图片 / iframe 懒加载，可跳过首屏前 N 张（保 LCP），支持 `data-no-lazy` 排除；
- DNS 预取（站点自身 + 后台附加域名，输出端格式白名单校验）。
