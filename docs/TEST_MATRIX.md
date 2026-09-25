# 测试矩阵

> 规范 §71。所有结果均来自 **真实测试站实测**（Z-BlogPHP 1.7.5 Build 3540 / PHP 8.3.33 / MySQL 5.7），
> 不是静态推断。执行脚本：`_market_test_110.py`（48 项）、`_market_life_110.py`（20 项）。

版本：**1.1.0**　　执行日期：2026-09-25

## 一、规范 §71 要求的矩阵

| 测试 | 结果 | 证据 |
|---|---|---|
| 安装 | **PASS** | 官方 `AppCentre/app_upload.php` 上传安装，HTTP 200；落地 `plugin.xml` version=1.1.0 |
| 启用 | **PASS** | 从插件管理页取本插件启用链接，启用后前台注入恢复 |
| 停用 | **PASS** | 停用后前台不再注入；配置完整保留 |
| 删除 | **PASS** | `rm -rf` 插件目录后，前台 HTTP 200 无致命错误，后台插件页可访问 |
| 重装 | **PASS** | 删除后重新安装 HTTP 200，落地 1.1.0，可启用 |
| 升级 | **PASS** | 1.0.9 → 1.1.0 官方安装升级；`ConfigVer` 保持 3；黑名单原样保留 |
| 后台保存 | **PASS** | 保存值与用户输入逐字一致，无追加、无改写 |
| 非管理员访问 | **PASS** | 未登录 → 拦截；Level 6 用户 → 进不了后台，看不到设置表单 |
| XSS | **PASS** | 8 组 payload（script 标签 / 闭合属性 / javascript: / data: / 路径穿越 / 属性注入）全部未产生可执行脚本 |
| CSRF | **PASS** | 无 token、错误 token 的保存与清除均被拒绝 |
| 非法域名 | **PASS** | `javascript:` / `<script>` / `data:` / `../../` / `" onload=` 全部被丢弃 |
| 重复域名 | **PASS** | `example.com ×3` → 库里只保存 1 条 |
| 空配置 | **PASS** | 全空保存无报错，前台不注入脚本，DNS 预取仍含站点自身域名 |
| 超长输入 | **PASS** | 100 KB 输入保存无报错，耗时 0.45s；>100 字符的行被丢弃；HTML 未膨胀 |
| 首页 | **PASS** | HTTP 200，无 PHP 报错，三个脚本均已注入 |
| 文章页 | **PASS** | 同上 |
| 分类页 | **PASS** | 同上 |
| 搜索页 | **PASS** | 同上 |
| JS | **PASS** | 语法正确、IIFE 包裹、`'use strict'`、无 eval / 无 innerHTML / 无动态 script / 无 console |
| CSS | **PASS** | 无 `!important`、无全局裸选择器、class 全部带 `at8ps-` 前缀 |

## 二、扩展项

| 测试 | 结果 | 证据 |
|---|---|---|
| 页面（page 类型） | **PASS** | `/?id=2` HTTP 200 且已注入 |
| 标签页 | **SKIP** | 测试站无标签数据，无法实测 |
| 清除插件配置 | **PASS** | 清除后本插件配置行数 = 0；其它 219 行配置不受影响；文章数据不受影响 |
| 清除配置 · CSRF | **PASS** | 无 token 的清除请求被拒绝 |
| 停用 → 重新启用后配置保留 | **PASS** | 标记值 `LIFE110` 全程保留 |
| 删除目录 → 重装后配置沿用 | **PASS** | 重装后黑名单仍为 `LIFE110`，`ConfigVer` 仍为 3 |
| 手动覆盖升级的兜底迁移 | **PASS** | 人为把 `ConfigVer` 退回 2，访问设置页后被兜底升回 3，黑名单未改动 |
| 性能开销 | **PASS** | 首页中位数耗时：启用 0.0280s / 停用 0.0279s，差值 +0.0001s（远低于 100ms 阈值） |
| 错误日志 | **PASS** | 新增日志中提及本插件的行数 = 0 |
| PHP Lint | **PASS** | `php -l` 对 `include.php` / `main.php` 在 PHP 8.3.33 全部通过 |

## 三、未实测项（如实记录）

| 项目 | 状态 | 原因 |
|---|---|---|
| PHP 7.4 / 8.1 / 8.2 / 8.4 运行时 | **NOT TESTED** | 测试站只装有 PHP 8.3.33。已做**静态语法扫描**：源码未使用任何 PHP 8.0~8.4 专有语法（`?->` / `match` / Attribute / 联合类型 / enum / readonly / json_validate / 属性钩子等） |
| 标签页 | **NOT TESTED** | 测试站无标签数据 |
| CDN / Cloudflare 等外部缓存 | **NOT TESTED** | 测试站无 CDN；已验证 Z-Blog 原生缓存下前台输出正常 |
| 真实浏览器悬停预加载行为 | **NOT TESTED** | 未做浏览器端端到端 hover 验证；依据为 vendored instant.page v5.2.0 的 `isPreloadable()` 源码 + DOM 沙箱仿真 |

## 四、汇总

```
_market_test_110.py   48 项   48 PASS / 0 FAIL
_market_life_110.py   20 项   20 PASS / 0 FAIL
market-audit.py       静态    0 FAIL / 0 WARN
_check_zba_struct.py  包结构  13/13 PASS（11 文件逐字节一致）
```
