# 第三方组件许可证报告

> 规范 §11 / §12 / §43。许可证以**实际源码**为准，不凭印象填写。

版本：1.1.0　　审计日期：2026-09-25

## 一、组件清单

本插件仅内置 **1 个**第三方组件，其余均为自研代码。

| 组件 | 版本 | 来源 | 许可证 | 使用方式 |
|---|---|---|---|---|
| instant.page | **v5.2.0** | https://instant.page/ | **MIT** © 2019–2025 Alexandre Dieulot | 本地内置 `assets/instantpage.js` |

自研（非第三方，同 MIT）：

| 文件 | 说明 |
|---|---|
| `assets/at8-guard.js` | 预加载守卫：黑名单关键词 → `data-no-instant` 标记 |
| `assets/at8-lazy.js` | 懒加载：为视口外 `img` / `iframe` 补 `loading="lazy"` |

## 二、版本与来源的取证过程

### 版本

源码首行注释（原样保留，未删改）：

```js
/*! instant.page v5.2.0 - (C) 2019-2025 Alexandre Dieulot - https://instant.page/license */
```

### 许可证

访问 https://instant.page/license 取得的原文为 MIT 许可证全文，版权行
`© 2019–2024 Alexandre Dieulot`（源码头注释写 2019–2025，以两者中较新的表述为准）。

MIT 要求「上述版权声明和本许可声明应包含在所有副本或实质部分中」——
源码头部的版权与许可证链接**原样保留**，已满足该要求。

## 三、代码完整性校验（§11「修改情况」）

| 项目 | 值 |
|---|---|
| 文件大小 | **17175 字节** |
| 行数 | 452 |
| MD5 | `9b05e316aedabe445698f148c00e9e5e` |
| SHA-256 | `63b3542e705a7b42d2d4f01b950986bfffde55d5bc6b0e11f90399e5d42a9ec6` |
| 比对基线 | `https://raw.githubusercontent.com/instantpage/instant.page/master/instantpage.js` |
| **比对结果** | **逐字节一致（未修改）** |

> 该校验已固化为自动化断言：`scripts/market-audit.py` 会比对 MD5，
> 一旦文件被改动即告警。

另注：官方 `https://instant.page/5.2.0` 提供的是**压缩版**（3051 B），
本项目采用的是与上游仓库一致的**未压缩版**，二者均为 v5.2.0。

## 四、运行时行为

- **本地加载**：插件输出 `<script src="<本站>/zb_users/plugin/at8_pagespeed/assets/instantpage.js">`，
  **不从 instant.page 官方服务器动态加载**
- 无遥测、无数据上报、不向任何服务器发送数据
- 不修改其行为：未设置 `data-instant-allow-query-string`，未给链接加 `data-instant`

## 五、结论

| 项 | 结论 |
|---|---|
| 版本 | **PASS**（v5.2.0 可从源码首行取证） |
| 来源 | **PASS**（官方站点 + 上游 GitHub 仓库） |
| 许可证 | **PASS**（MIT，原文已核实，版权声明原样保留） |
| 代码完整性 | **PASS**（与上游逐字节一致，未修改） |
| 使用方式 | **PASS**（本地内置，非运行时远程加载） |

完整信息同时写入随包分发的 `THIRD-PARTY-LICENSES.md`。
