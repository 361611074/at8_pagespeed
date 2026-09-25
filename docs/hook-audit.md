# Hook 审计

> 规范 §6 / §53：每个 Hook 都必须能回答「为什么需要？为什么不能用更简单的？会影响哪些页面？」

**结论：本插件共注册 2 个过滤器 + 3 个生命周期钩子。无任何业务流程类 Hook。**

---

## 一、运行期过滤器（ActivePlugin 中注册）

### 1. `Filter_Plugin_Zbp_MakeTemplatetags`

| 项目 | 内容 |
|---|---|
| **Hook** | `Filter_Plugin_Zbp_MakeTemplatetags` |
| **回调** | `at8_pagespeed_tags(&$tags)` |
| **用途** | 向前台模板标签追加优化输出：`$tags['header']` 追加 DNS 预取 `<link>`，`$tags['footer']` 追加懒加载与预加载脚本 |
| **触发时机** | Z-BlogPHP 构建前台模板标签时（系统原生前台注入点） |
| **输入** | `&$tags` 数组（引用传递） |
| **输出** | 无返回值；仅**追加**字符串到 `$tags['header']` / `$tags['footer']` |
| **是否修改系统行为** | **否**。只追加内容，不改写已有值，不接管任何系统流程 |
| **影响哪些页面** | 仅前台（`at8_pagespeed_is_frontend()` 判定，后台 / 登录页 / `/zb_system/` 下请求一律跳过） |
| **风险** | 低。依赖主题输出标准变量 `$header` / `$footer`；主题未输出时静默跳过，不报错 |
| **为什么需要** | 这是 1.7.x 官方唯一的前台输出注入点。*（注：`Filter_Plugin_Zbp_Header/Footer` 在 1.7.5 并不存在，挂上去会静默空挂 —— 已在 1.0.1 修正）* |
| **为什么不能用更简单的** | 无更简单的官方注入点。不使用输出缓冲改写（那会接管整个页面输出，风险高得多） |

### 2. `Filter_Plugin_Admin_LeftMenu`

| 项目 | 内容 |
|---|---|
| **Hook** | `Filter_Plugin_Admin_LeftMenu` |
| **回调** | `at8_pagespeed_menu(&$m)` |
| **用途** | 在后台左侧菜单追加一个「页面加速」入口 |
| **触发时机** | 后台页面构建左侧菜单时 |
| **输入** | `&$m` 菜单数组（引用传递） |
| **输出** | 无返回值；追加 `$m['nav_at8_pagespeed']`（`MakeLeftMenu()` 生成，`admin` 权限） |
| **是否修改系统行为** | **否**。只加一个菜单项，不改动任何已有菜单 |
| **影响哪些页面** | 仅后台页面 |
| **风险** | 低。`MakeLeftMenu()` 第一个参数为 `'admin'`，非管理员不显示 |

---

## 二、生命周期钩子

| 钩子 | 行为 | 说明 |
|---|---|---|
| `InstallPlugin_at8_pagespeed()` | 幂等写入默认配置（仅补齐缺失键，不覆盖已存值） | 安装 / 停用后再启用都会进入 |
| `UpdatePlugin_at8_pagespeed()` | 调用 `at8_pagespeed_migrate_config()` | 官方升级钩子 |
| `at8_pagespeed_Updated()` | 转发到 `UpdatePlugin_at8_pagespeed()` | 1.7 之前的旧命名兼容 |
| `UninstallPlugin_at8_pagespeed()` | **有意空实现** | 见下 |

### 关于 `UninstallPlugin_at8_pagespeed()` 为何为空

从 1.7.5 核心源码实证：**`DisablePlugin()` 内部会调用 `UninstallPlugin_<id>()`**，即
「停用」与「卸载」共用同一个钩子；而真正的「删除应用」由应用中心删除插件目录完成，
**反而不触发**这个钩子。

因此若在该钩子中删除配置，用户每次「停用」都会丢失全部自定义设置。
本插件把配置视为站点级偏好予以保留；需要彻底清理时由管理员在
「设置 → 高级 → 清除插件配置」主动执行。

---

## 三、确认「不存在」的 Hook（§6 重点检查项）

逐一确认本插件**未注册**以下任何一类 Hook：

| 类别 | 是否存在 |
|---|---|
| 系统登录 / 鉴权 | 无 |
| 文章发布 / 编辑 / 删除 | 无 |
| 评论 | 无 |
| 用户 / 会员 | 无 |
| 上传 / 附件 | 无 |
| 删除类（`*Del` / `*Edt` / `*Sav`） | 无 |
| 数据库操作 | 无 |
| 系统 URL / 路由改写 | 无 |
| 输出缓冲接管（`ob_start`） | 无 |

**依赖声明**：`plugin.xml` 的 `<advanced>` 中 `<dependency>` / `<rewritefunctions>` /
`<existsfunctions>` / `<conflict>` 均为空 —— 不重写函数、不依赖其它插件、无已知冲突。
