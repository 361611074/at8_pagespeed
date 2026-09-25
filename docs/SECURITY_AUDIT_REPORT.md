# 安全审计报告

> 规范 §54：每条用户输入都必须追踪完整的「输入 → 处理 → 存储 → 输出」数据流。

版本：1.1.0　　审计日期：2026-09-25

## 一、用户输入清单与数据流

本插件只有 **3 处**用户输入，全部来自后台设置页（需 `admin` 权限 + CSRF 校验）。

### 1. 预加载黑名单（textarea `blacklist`）

| 阶段 | 处理 |
|---|---|
| 输入 | `GetVars('blacklist','POST')`，`trim()` |
| 处理 | 按行拆分 → `trim()` → 丢弃空行与长度 >100 的行 → **原样保留**（不追加、不删除、不改写） |
| 存储 | `$c->blacklist = implode("\n", $clean)`，写入本插件配置行 |
| 输出 | `json_encode($arr, JSON_UNESCAPED_UNICODE \| JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS \| JSON_HEX_QUOT)` 写入 `<script>` |

**XSS 防护**：`JSON_HEX_TAG` 把 `<` `>` 转成 `\u003C` `\u003E`，`JSON_HEX_AMP/APOS/QUOT` 处理 `&` `'` `"`。
因此即使关键字含 `</script>` 或 `<!--`，也无法闭合 script 上下文。

**实测**：`<script>alert(1)</script>`、`"><script>alert(1)</script>`、`javascript:alert(1)` 三组 payload
保存后，前台页面均**未出现**可执行脚本。

### 2. DNS 预取域名（textarea `dns_domains`）

| 阶段 | 处理 |
|---|---|
| 输入 | `GetVars('dns_domains','POST')`，`trim()` |
| 处理 | `at8_pagespeed_normalize_domain()`：去协议 → 去路径/查询/锚点 → 长度 ≤253 → 正则 `^[a-z0-9][a-z0-9.-]*(\.[a-z0-9.-]+)+$` 校验 → 去重 |
| 存储 | 仅写入**通过校验**的域名 |
| 输出 | `htmlspecialchars($d, ENT_QUOTES, 'UTF-8')` |

**纵深防御**：保存时与输出时**都**调用同一个归一化函数，库里不落脏数据，输出也不可能带出脏数据。

**实测**：`javascript:alert(1)`、`<script>alert(1)</script>`、`data:text/html,...`、`../../../etc/passwd`、
`" onload="alert(1)` 五组 payload 全部被丢弃，前台只输出站点自身域名。

### 3. 数值项（`preload_delay` / `lazy_skip` / 两个开关）

`max(0, min(2000, (int) ...))`、`max(0, min(20, (int) ...))` —— 强制整型 + 区间钳制，无注入面。

## 二、逐项结论

| 项目 | 结论 | 依据 |
|---|---|---|
| XSS | **PASS** | 黑名单经 `JSON_HEX_*` 编码；域名经正则白名单 + `htmlspecialchars`；所有 `echo $xxx` 均带转义或 `(int)` 转换。8 组 payload 实测无可执行脚本 |
| CSRF | **PASS** | 所有写操作（保存 / 清除）先 `CheckIsRefererValid()`，失败即 `ShowError(5)` 终止；表单输出 `GetCSRFToken()`。实测：无 token、错误 token 均被拒 |
| 权限 | **PASS** | 后台页 `CheckRights('admin')` + `CheckPlugin('at8_pagespeed')`，失败 `ShowError(6/48)`。实测：未登录被拦截；Level 6 用户进不了后台 |
| SQL 注入 | **PASS** | **不使用任何 SQL**。全部数据读写走官方配置接口 `$zbp->Config()` / `SaveConfig()` / `DelConfig()`。`Database Table = NONE`、`Direct SQL = NONE` |
| 文件操作 | **PASS** | 源码无 `fopen` / `file_put_contents` / `unlink` / `rename` / `copy` / `mkdir` / `rmdir` / `scandir` / `glob`。`File Write = NONE` |
| 远程代码执行 | **PASS** | 无 `eval` / `assert` / `base64_decode` / `shell_exec` / `exec` / `system` / `passthru` / `proc_open` / `popen`；无远程 include；`include` / `require` 路径全部为字面量 |
| 危险函数 | **PASS** | 见上。注意：源码中出现过 `assert` 字样仅在注释中，非调用 |
| 日志泄露 | **PASS** | 无 `error_log` / `var_dump` / `print_r` / `var_export`；JS 无 `console.*` |
| 开放重定向 | **PASS** | 保存后固定 `Redirect('./main.php')`，不读取 `HTTP_REFERER` |

## 三、网络行为

| 项目 | 结论 |
|---|---|
| 外部请求 | **NONE**。无 `curl_exec` / `file_get_contents` / `fsockopen` / `stream_socket_client` |
| 前端网络 | **NONE**。JS 无 `fetch` / `XMLHttpRequest` / `sendBeacon` |
| 遥测 / 统计 | **NONE** |
| 远程资源引用 | **NONE**。无任何外站 `<script src>` / `<link href>` / CSS `url()` |
| 数据收集 | **NONE**。配置只存本站，不外发 |

## 四、边界确认

- 不注册上传 / 删除 / 发布 / 评论 / 用户 / 路由等任何业务流程 Hook（详见 `hook-audit.md`）
- 前台 JS 只给链接加 `data-no-instant`（含义＝不预加载），**不**调用 `preventDefault` / `stopPropagation`、
  **不** `return false`、**不**修改 `href`
- 不修改 instant.page 内置逻辑，不开启 query string 预加载（未设置 `data-instant-allow-query-string`）
