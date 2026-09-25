# 第三方组件与许可证

本插件（`at8_pagespeed` · 页面加速）自身采用 **MIT License**，详见 [`LICENSE`](LICENSE)。

除下述组件外，本插件不含其它第三方代码。

---

## instant.page

| 项目 | 内容 |
|---|---|
| 组件名称 | instant.page |
| 版本 | **v5.2.0** |
| 官方来源 | https://instant.page/ |
| 上游仓库 | https://github.com/instantpage/instant.page |
| 许可证 | **MIT License** |
| 版权 | © 2019–2025 Alexandre Dieulot |
| 许可证原文 | https://instant.page/license |
| 项目中的使用方式 | **本地内置**（`assets/instantpage.js`），随插件一起分发，**不在运行时从 instant.page 官方服务器动态加载** |
| 本插件是否修改过 | **未修改**，与上游源码逐字节一致（见下方校验） |
| 运行时网络行为 | 无。该脚本只做链接预取，不向任何服务器发送数据、不含遥测 |

### 代码完整性校验

| 项目 | 值 |
|---|---|
| 文件大小 | 17175 字节 |
| 行数 | 452 |
| MD5 | `9b05e316aedabe445698f148c00e9e5e` |
| SHA-256 | `63b3542e705a7b42d2d4f01b950986bfffde55d5bc6b0e11f90399e5d42a9ec6` |
| 比对基线 | `https://raw.githubusercontent.com/instantpage/instant.page/master/instantpage.js` |
| 比对结果 | **逐字节一致** |

源文件头部自带的版权与许可证声明（原样保留，未作删改）：

```js
/*! instant.page v5.2.0 - (C) 2019-2025 Alexandre Dieulot - https://instant.page/license */
```

### MIT 许可证原文（instant.page）

> © 2019–2024 Alexandre Dieulot
>
> Permission is hereby granted, free of charge, to any person obtaining a copy of this software
> and associated documentation files (the "Software"), to deal in the Software without restriction,
> including without limitation the rights to use, copy, modify, merge, publish, distribute,
> sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
> furnished to do so, subject to the following conditions:
>
> The above copyright notice and this permission notice shall be included in all copies or
> substantial portions of the Software.
>
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
> BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
> NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
> DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
> OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

## 本插件的两个配套脚本

`assets/at8-guard.js` 与 `assets/at8-lazy.js` 为**本项目自研代码**，非第三方组件，
与本插件同采用 MIT License，版权归本插件作者所有。二者无外部依赖、不引用任何外站资源。
