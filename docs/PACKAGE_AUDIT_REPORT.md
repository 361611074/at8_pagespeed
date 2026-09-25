# 发布包二次审计报告（Package Audit Report）

> 规范 §75「发布包二次审计」/ §79 第 11 步。判定对象为**实际交付的 .zba 文件本体**，
> 不是源码目录的推断。校验方式：解压 gzip → 解析 XML → 逐文件 base64 还原 → 与源码目录逐字节比对。

版本：**1.1.0**　　审计日期：2026-09-25

## 一、包标识

| 项目 | 值 |
|---|---|
| 文件名 | `at8_pagespeed_1.1.0_20260925.zba` |
| 体积 | **50459 B**（49.3 KB） |
| md5 | `e8554d0d15fac7f241ab6d166e2c8533` |
| sha256 | `ce3c89c89f7db22f6ebcaa74b60a3e0707713a24a62c3b547f393cf97f71242f` |
| 容器格式 | gzip（魔数 `1F 8B`）+ XML（`<app version="php" type="plugin">`） |
| 解压后 XML | 114497 B |
| 包内文件 | 11 个文件 + 1 个目录 |
| 源文件总字节 | 83955 B |

> `.zba` 不是 zip。Z-BlogPHP 1.7.5 `zb_system/function/lib/app.php` 的 `App::UnPack()`
> 先判断前两字节是否为 `0x1F 0x8B`，是则 `gzdecode`，再按 XML 解包。
> 用 `zipfile` 打开会报 `BadZipFile` —— 这是**正常现象**，非包损坏。

## 二、结构断言（`_check_zba_struct.py` → 13/13 PASS）

| # | 断言 | 结果 | 实测值 |
|---|---|---|---|
| S1 | gzip 魔数 `1F 8B`（否则官方不 gzdecode） | **PASS** | `1F 8B` |
| S2 | 根节点 `version="php"` | **PASS** | `php` |
| S3 | 根节点 `type="plugin"` | **PASS** | `plugin` |
| S4 | `<id>` 存在且等于插件目录名 | **PASS** | `at8_pagespeed` |
| S5 | `<folder>` 节点结构完整（含 `<path>`） | **PASS** | 1 个 |
| S6 | `<file>` 节点结构完整（含 `<path>` + `<stream>`） | **PASS** | 11 个，异常 0 |
| S7 | 所有 `<file><path>` 以 `<id>/` 开头 | **PASS** | — |
| S8 | 无 `./` 路径穿越片段 | **PASS** | — |
| S9 | 包内文件内容与源目录**逐字节一致** | **PASS** | 11/11 一致 |
| S10 | 含 `<version>` | **PASS** | `1.1.0` |
| S10 | 含 `<phpver>` | **PASS** | `7.4` |
| S10 | 含 `<adapted>` | **PASS** | `173510` |
| S10 | 含 `<price>` | **PASS** | `0` |

## 三、包内文件清单（11 个，逐字节核验）

| 路径 | 字节 | md5 |
|---|---|---|
| `at8_pagespeed/plugin.xml` | 4004 | `b300367b3b0368c1fa0004ba835cd8e7` |
| `at8_pagespeed/include.php` | 14367 | `41d7b790f1db6f26f1fd8c136501e8bb` |
| `at8_pagespeed/main.php` | 9374 | `168d568ca199df699b11ee3ae8cc4535` |
| `at8_pagespeed/style.css` | 2531 | `373133c5da04b57deec9477bcde02674` |
| `at8_pagespeed/logo.png` | 1866 | `639e5ecf8b0501b0fa266b15765ee03b` |
| `at8_pagespeed/assets/at8-guard.js` | 4906 | `31d97702bb60d23d89a614aad917d7e1` |
| `at8_pagespeed/assets/at8-lazy.js` | 3221 | `689b4fb235939fffe586fafb49074c22` |
| `at8_pagespeed/assets/instantpage.js` | 17175 | `9b05e316aedabe445698f148c00e9e5e` |
| `at8_pagespeed/LICENSE` | 1266 | `03e5e4d3bfd17c03bf97573600f0b820` |
| `at8_pagespeed/README.md` | 22368 | `9f9ce217ac4ecf188567f34b59575e54` |
| `at8_pagespeed/THIRD-PARTY-LICENSES.md` | 2877 | `af4ba163a4c1e60ef04961c5083f85ac` |

目录节点：`at8_pagespeed/assets/`（1 个）

## 四、禁止内容核验（17 项全 PASS）

### 4.1 开发/仓库文件

| 项 | 结果 |
|---|---|
| `.git` 未入包 | **PASS** |
| `screenshots/` 未入包 | **PASS** |
| `cache/` 未入包 | **PASS** |
| `zbignore.txt` 未入包 | **PASS** |
| `CHANGELOG.md` 未入包 | **PASS** |
| `RELEASE_CHECKLIST.md` 未入包 | **PASS** |
| `docs/` 未入包 | **PASS** |
| `scripts/` 未入包 | **PASS** |
| `release-manifest.txt` 未入包 | **PASS** |
| `release-forbidden.txt` 未入包 | **PASS** |

### 4.2 必需文件

| 项 | 结果 |
|---|---|
| `plugin.xml` 入包 | **PASS** |
| `include.php` 入包 | **PASS** |
| `main.php` 入包 | **PASS** |
| `logo.png` 入包 | **PASS** |
| `LICENSE` 保留 | **PASS** |
| `THIRD-PARTY-LICENSES.md` 保留（MIT 分发义务） | **PASS** |
| `README.md` 保留（已确认的取舍） | **PASS** |

## 五、敏感信息扫描

扫描对象：解压后的完整 XML（含全部 base64 流解码后的语义上下文）。

| 模式 | 结果 |
|---|---|
| 本机路径 `C:\Users` / `C:/Users` | **未发现** |
| 生产站域名 `ccava.net` | **未发现** |
| 测试站域名 `zblog.xmm.fan` | **未发现** |
| 数据库账号 / 密码 / 密钥 / 证书 | **未发现** |
| `*.sql` / `*.log` / `*.pem` / `*.key` | **未发现** |
| 生产站管理员 ID `361611074` 的非邮箱出现 | **未发现** |

唯一命中项：作者邮箱 `361611074@qq.com`（出现 2 次，位于 `plugin.xml` 的 `<author>` 与 `<source>` 节点）
—— 属应用中心**必填**的对外联系信息，与 GitHub 账号一致，判定为**放行**。

## 六、构建可复现性

`_verify_final_zba.py` 用 Python 复刻 `build_zba.php` 的构建口径
（`zbignore.txt` 规则 + 强制忽略项 + PHP 文件 BOM 剥离 + `htmlspecialchars` 默认转义语义），
对当前源码目录重新生成 XML，与包内 XML 做**逐字节比对**：

```
与当前源码重建结果逐字节比对: 一致 OK
```

说明包内容与当前 HEAD 源码严格对应，无「源码已改但包未重建」的漂移。

## 七、包体积构成分析

| 组成 | 字节 | 占比 |
|---|---|---|
| `instantpage.js`（第三方 vendored） | 17175 | 20.5% |
| `README.md` | 22368 | 26.6% |
| PHP（`include.php` + `main.php`） | 23741 | 28.3% |
| `THIRD-PARTY-LICENSES.md` | 2877 | 3.4% |
| 其余（js/css/logo/xml/LICENSE） | 17794 | 21.2% |
| **合计** | **83955** | 100% |

gzip 压缩后 50459 B。相对 1.0.9 包（44704 B）增大约 5.6 KB，主要来自
新增的 `THIRD-PARTY-LICENSES.md`、README 扩充章节与新增的「清除插件配置」功能代码。

## 八、结论

```
结构断言      13/13 PASS
逐字节一致性  11/11 PASS
禁止内容      17/17 PASS
敏感信息      PASS（仅作者邮箱，属必填）
构建可复现    PASS
------------------------------------------------
总判定        PASS
```

发布包可直接提交 Z-Blog 应用中心。
