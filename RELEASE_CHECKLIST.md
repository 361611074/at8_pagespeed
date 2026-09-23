# at8_pagespeed 1.0.2 — 发布检查清单（RELEASE_CHECKLIST）

检查基准：《Z-BlogPHP 插件 AI Agent 开发规范》§29
实测环境：Z-BlogPHP **1.7.5** / PHP 7.3.4（本地 `php -l`）+ PHP 8.2（测试站 zblog.xmm.fan）/ MySQL
检查日期：2026-09-23

## 1. 元数据与标识

| 项 | 值 | 结果 |
|---|---|---|
| 插件 ID | `at8_pagespeed` | ✅ 长期稳定，未随重构改名 |
| 插件名称 | 页面加速 | ✅ |
| 版本号 | 1.0.2（`plugin.xml` 与 `AT8_PAGESPEED_VERSION` 一致） | ✅ 十进制封十进一 |
| 目录 / 文件前缀 | 函数 `at8_pagespeed_*`、CSS `.ps-*`、JS `window.at8Ps*`、配置键 `conf_Name=at8_pagespeed` | ✅ 无通用命名 |
| `plugin.xml` 必填节点 | id/name/url/note/description/path/include/level/author/source/adapted/version/pubdate/modified/price/**phpver**/advanced | ✅ 齐全 |
| 作者与官网 | 漫步白月光 / https://www.at8.fun/ | ✅ |

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
- 安装回归：**23 项断言 23 PASS / 0 FAIL**（干净包经官方「应用中心 → 上传应用」链路实装；落地清单与包内清单严格相等；无 `.git` 残留）
- 界面回归（1.0.2）：**17 项断言 17 PASS / 0 FAIL** —— 设置页已接入官方后台框架，`admin2.css` / `zblogphp.js` 已加载、顶栏 `#topmenu` 与左侧菜单 14 项（含 `nav_at8_pagespeed`）正常渲染、仅 1 个 doctype、无 PHP 告警、保存往返正常
- 布局对齐实测（1440×900）：`#divMain` 宽 1280 / 起点 x=150 / `max-width:none`、内容容器内距 `20px 24px 60px` / `max-width:1400px` —— 与 `at8_media_library` 设置页逐项一致
- 回归复现脚本：`audit_pagespeed.py`、`audit_repro_disable.py`、`test_zba_install_clean.py`、`deploy_ps_102.py`

## 8. 发布物

- `at8_pagespeed_1.0.2_20260923.zba`（**25.4 KB，9 文件**；真实插件文件 + `LICENSE`，已剔除 README / CHANGELOG / RELEASE_CHECKLIST / zbignore / `cache` / `.git`）
  > ⚠️ 打包污染教训：本插件目录内就是 git 工作区，早期打包脚本只按 `zbignore.txt` 排除，
  > 导致 `.git` 整棵树（35 个文件、约 51 KB）被打进分包。现已在 `build_zba.php` 加入
  > 「不依赖 zbignore 的强制排除清单」，并对包内文件做逐文件 MD5 校验（`_verify_zba.py`）。
- GitHub：https://github.com/361611074/at8_pagespeed
