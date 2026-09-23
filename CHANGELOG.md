# 更新日志 · at8_pagespeed

版本号规则：十进制封十进一（每段 0~9，满 10 进位），不用 1.2.10 这类写法。

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
