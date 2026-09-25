# AT8 Pagespeed
# Z-Blog Market Final Audit Report

Version:
1.1.0

Audit Date:
2026-09-25

Audit Basis:
Z-BlogPHP 1.7.5 Build 3540 / PHP 8.3.33 / MySQL 5.7
测试站 http://zblog.xmm.fan
发布包 `at8_pagespeed_1.1.0_20260925.zba`（50459 B，md5 `e8554d0d15fac7f241ab6d166e2c8533`）

================================
1. OVERALL RESULT
================================

P0:
0

P1:
0

P2:
5
（全部为「测试环境未覆盖」类，非代码缺陷，逐条列于第 9 节）

Result:
READY WITH WARNINGS

================================
2. SECURITY
================================

XSS:
PASS
（黑名单经 `json_encode(..., JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)` 编码；
 域名经 `at8_pagespeed_normalize_domain()` 正则白名单 + `htmlspecialchars(ENT_QUOTES)`；
 全部 `echo` 带转义或 `(int)`。8 组 payload 实测无可执行脚本。）

CSRF:
PASS
（保存 / 清除两条写路径均先 `CheckIsRefererValid()`，失败即 `ShowError(5)` 终止；
 表单输出 `GetCSRFToken()`。实测无 token、错误 token 均被拒。）

Permission:
PASS
（后台页 `CheckRights('admin')` + `CheckPlugin('at8_pagespeed')`；
 实测未登录被拦截、Level 6 用户进不了后台。）

SQL Injection:
PASS
（Direct SQL = NONE。全部读写走官方 `$zbp->Config()` / `SaveConfig()` / `DelConfig()`。）

File Operation:
PASS
（File Write = NONE。无 fopen / file_put_contents / unlink / rename / copy / mkdir / rmdir / scandir / glob。）

Remote Code:
PASS
（无 eval / assert / base64_decode；`include` / `require` 路径全部为字面量，无变量拼接。）

Command Execution:
PASS
（无 shell_exec / exec / system / passthru / proc_open / popen。）

================================
3. NETWORK
================================

External Request:
NONE
（PHP 无 curl_exec / file_get_contents / fsockopen / stream_socket_client；
 前端 JS 无 fetch / XMLHttpRequest / sendBeacon。）

Telemetry:
NONE

Remote Resource:
NONE
（无任何外站 `<script src>` / `<link href>` / CSS `url()`。
 前台注入的 3 个脚本全部指向插件自身 `assets/` 目录。）

================================
4. THIRD PARTY
================================

instant.page:
PASS
（vendored v5.2.0，位于 `assets/instantpage.js`，452 行 / 17175 B / 未压缩仓库版。
 md5 `9b05e316aedabe445698f148c00e9e5e`
 sha256 `63b3542e705a7b42d2d4f01b950986bfffde55d5bc6b0e11f90399e5d42a9ec6`
 与上游 `https://raw.githubusercontent.com/instantpage/instant.page/master/instantpage.js`
 逐字节一致。）

License:
PASS
（MIT，© 2019–2025 Alexandre Dieulot。随包分发 `THIRD-PARTY-LICENSES.md` 全文，
 见 `THIRD_PARTY_LICENSE_REPORT.md`。）

Source:
PASS
（上游仓库 instantpage/instant.page；包内文件名、版本号、md5 均已记录可复现。
 说明：上游 `/5.2.0` 路径提供的是 3051 B 压缩版，本插件使用的是仓库未压缩版，
 功能等价，已在 `THIRD-PARTY-LICENSES.md` 中显式声明。）

================================
5. LIFECYCLE
================================

Install:
PASS
（官方 `AppCentre/app_upload.php` 上传安装 HTTP 200，落地 `plugin.xml` version=1.1.0。）

Enable:
PASS

Disable:
PASS
（停用后前台不再注入，配置完整保留。）

Delete:
PASS
（删除插件目录后前台 HTTP 200 无致命错误，后台插件页可访问。）

Reinstall:
PASS

Upgrade:
PASS
（1.0.9 → 1.1.0 官方安装升级；`ConfigVer` 保持 3；用户黑名单原样保留。
 手动覆盖文件升级的兜底迁移亦已实测：人为把 `ConfigVer` 退回 2，
 访问设置页后被兜底升回 3，黑名单未被改动。）

详见 `LIFECYCLE_AUDIT_REPORT.md`。

================================
6. FUNCTION
================================

Lazy Load:
PASS
（图片懒加载，跳过首屏前 2 张；停用后立即失效，无残留。）

DNS Prefetch:
PASS
（`x-dns-prefetch-control: on` + 站点自身域名；自定义域名经白名单校验后输出。）

Link Preload:
PASS
（instant.page v5.2.0 悬停预加载；注入 `data-instant-intensity`；
 预加载黑名单经 `window.at8PsBlacklist` 下发，guard 脚本对命中链接加 `data-no-instant`，
 **不**调用 preventDefault / stopPropagation，**不**修改 href，**不**开启 query string 预加载。）

前台实测：首页 / 文章页 / 分类页 / 搜索页 / page 页 全部 HTTP 200 且注入完整。

================================
7. PACKAGE
================================

ZBA:
PASS
（gzip 魔数 1F 8B；根节点 `version="php" type="plugin"`；`<id>` = 目录名；
 11 个 `<file>` 全部以 `<id>/` 开头，无 `./` 穿越；
 包内 11 个文件与源目录**逐字节一致**。）

Forbidden Files:
PASS
（`.git` / `screenshots/` / `cache/` / `zbignore.txt` / `CHANGELOG.md` /
 `RELEASE_CHECKLIST.md` / `docs/` / `scripts/` / `release-manifest.txt` /
 `release-forbidden.txt` 均未入包。逐项断言见 `PACKAGE_AUDIT_REPORT.md`。）

Secrets:
PASS
（无本机路径 `C:\Users`、无生产站域名、无密钥/证书/SQL/日志。
 仅含应用中心必填的作者邮箱 `361611074@qq.com`。）

Version:
PASS
（`plugin.xml <version>` == `include.php` 常量 == 包内 `<version>` == 1.1.0。）

Manifest:
PASS
（`release-manifest.txt` 白名单 + `release-forbidden.txt` 黑名单均已建立，
 白名单与包内实际 11 个文件完全吻合。）

================================
8. DOCUMENTATION
================================

README:
PASS
（含功能说明、设置项与默认值及理由、清除配置说明、兼容性声明、更新日志。）

Privacy:
PASS
（README 独立「隐私」章节：不联网 / 不遥测 / 不统计 / 不上传 / 无账号 /
 第三方资源清单 / DNS 预取语义澄清。与实际代码行为一致。）

License:
PASS
（`LICENSE` 随包分发。）

Third Party:
PASS
（`THIRD-PARTY-LICENSES.md` 随包分发，含完整 MIT 原文与可复现校验值。）

================================
9. REMAINING ISSUES
================================

P0:
None

P1:
None

P2:
1. PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.4 运行时未实测。
   测试站仅安装 PHP 8.3.33。已做静态语法扫描：源码未使用任何 PHP 8.0~8.4 专有语法
   （`?->` / `match` / Attribute / 联合类型 / `enum` / `readonly` / `json_validate` /
   属性钩子 / `#[\Override]`），声明下限 `<phpver>7.4</phpver>` 保守合理。
   风险：低。

2. 标签页（tag）未实测。
   测试站无标签数据，无法构造。注入逻辑与其他页面类型共用同一 Hook 出口，无独立分支。
   风险：极低。

3. 真实浏览器 hover 端到端预加载行为未做自动化断言。
   现有依据为 vendored instant.page v5.2.0 的 `isPreloadable()` 源码 +
   DOM 沙箱仿真（`_guard_sim_109.js`）+ 线上页面注入内容实测。
   `isPreloadable()` 明确：未设置 `data-instant-allow-query-string` 且链接带 query
   且无 `data-instant` 时直接 `return`，故对 `cmd.php?act=XxxDel` 类带参操作链接
   预加载保护始终生效。
   风险：低。

4. CDN / Cloudflare 等外部缓存场景未实测。
   测试站无 CDN。已验证 Z-Blog 原生缓存下前台输出正常。
   风险：低（插件不接管缓存，仅注入前端资源）。

5. `README.md` 与 `THIRD-PARTY-LICENSES.md` 随包分发。
   Z-Blog 官方对包内文档无硬性限制，且第三方许可证文件属**分发义务**（MIT 要求保留版权声明），
   故保留。`CHANGELOG.md` / `RELEASE_CHECKLIST.md` / `docs/` / `scripts/` 等开发文档已排除。
   风险：无（仅为体积与整洁度取舍）。

================================
10. FINAL DECISION
================================

READY FOR Z-BLOG MARKET

（对应规范 §80 的 **READY WITH WARNINGS**：无 P0、无 P1，核心测试与发布包全部通过，
 剩余 5 项均为「测试环境未覆盖」的信息性提示，不构成提交阻碍。
 判定依据为实际代码、实际测试、实际发布包，未凭主观感觉。）
