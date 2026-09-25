#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
at8_pagespeed · Z-Blog 市场审核自动化检查脚本（规范 §56）

自动检查：
  版本一致性 / 插件 ID 一致性 / 危险函数 / 网络函数 / 远程 URL
  / 动态包含 / 文件操作 / SQL / 日志泄露 / CSS !important 与全局选择器
  / JS 危险 API / 目录污染 / 发布包结构与内容

用法：
  python scripts/market-audit.py            # 源码审计
  python scripts/market-audit.py --zba X    # 追加发布包审计

退出码 0 = 全部通过；1 = 存在 FAIL
"""
from __future__ import print_function

import io
import os
import re
import sys
import xml.etree.ElementTree as ET

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)

# 扫描范围：业务代码（不含 vendored 第三方与文档）
VENDORED = {"instantpage.js"}
DOC_FILES = {"README.md", "CHANGELOG.md", "RELEASE_CHECKLIST.md", "THIRD-PARTY-LICENSES.md"}

fail = []
warn = []


def check(label, cond, detail=""):
    print(("  PASS  " if cond else "  FAIL  ") + label + ("  | " + str(detail) if detail else ""))
    if not cond:
        fail.append(label)


def soft(label, cond, detail=""):
    print(("  OK    " if cond else "  WARN  ") + label + ("  | " + str(detail) if detail else ""))
    if not cond:
        warn.append(label)


def rd(p):
    with io.open(p, "r", encoding="utf-8", errors="replace") as f:
        return f.read()


def walk(exts, skip_dirs=(".git", "cache", "screenshots", "docs", "scripts")):
    out = []
    for dp, dn, fn in os.walk(ROOT):
        dn[:] = [d for d in dn if d not in skip_dirs]
        for f in fn:
            if f.endswith(exts):
                out.append(os.path.join(dp, f))
    return sorted(out)


def rel(p):
    return os.path.relpath(p, ROOT).replace("\\", "/")


def grep_files(pattern, exts, skip_vendored=True):
    """返回 [(文件相对路径, 命中行)]"""
    hits = []
    rx = re.compile(pattern)
    for p in walk(exts):
        if skip_vendored and os.path.basename(p) in VENDORED:
            continue
        for i, line in enumerate(rd(p).splitlines(), 1):
            if rx.search(line):
                hits.append((rel(p), i, line.strip()[:110]))
    return hits


print("=" * 70)
print("at8_pagespeed · Z-Blog 市场静态审计")
print("=" * 70)

# ---------------- 1. 版本一致性 ----------------
print("\n### 1. 版本一致性")
xmlp = os.path.join(ROOT, "plugin.xml")
root = ET.fromstring(rd(xmlp))
x_ver = root.findtext("version")
x_id = root.findtext("id")
x_php = root.findtext("phpver")
x_ad = root.findtext("adapted")
inc = rd(os.path.join(ROOT, "include.php"))
m = re.search(r"define\('AT8_PAGESPEED_VERSION',\s*'([^']+)'\)", inc)
c_ver = m.group(1) if m else None
readme = rd(os.path.join(ROOT, "README.md"))
chlog = rd(os.path.join(ROOT, "CHANGELOG.md"))

check("plugin.xml <version> == include.php 常量", x_ver == c_ver, "%s vs %s" % (x_ver, c_ver))
check("README 含该版本号", x_ver in readme)
check("CHANGELOG 含该版本号章节", ("## %s" % x_ver) in chlog or ("### %s" % x_ver) in chlog)
soft("plugin.xml <phpver> 已声明", bool(x_php), x_php)
soft("plugin.xml <adapted> 已声明", bool(x_ad), x_ad)
soft("plugin.xml <id> == 目录名", x_id == os.path.basename(ROOT), x_id)

# ---------------- 2. 插件 ID 一致性 ----------------
print("\n### 2. 插件 ID 一致性（禁止 at8-pagespeed / at8PageSpeed / at8pagespeed 混用）")
bad = []
for p in walk((".php", ".js", ".css", ".xml")):
    if os.path.basename(p) in VENDORED:
        continue
    t = rd(p)
    for form in ("at8-pagespeed", "at8PageSpeed", "at8pagespeed"):
        if form in t:
            bad.append((rel(p), form))
check("无错误 ID 写法", not bad, bad[:5])
hits = grep_files(r"at8_pagespeed", (".php", ".js", ".css", ".xml"))
soft("at8_pagespeed 出现于 %d 处" % len(hits), len(hits) > 0)

# ---------------- 3. 危险函数 ----------------
print("\n### 3. 危险函数（eval / assert / base64_decode / 命令执行）")
DANGER = r"\b(eval|assert|base64_decode|shell_exec|passthru|proc_open|popen|system|exec)\s*\("
h = grep_files(DANGER, (".php",))
h = [x for x in h if not x[0].startswith("scripts/")]
check("PHP 业务代码无危险函数", not h, h[:5])
hjs = grep_files(r"\beval\s*\(", (".js",))
check("JS 无 eval", not hjs, hjs[:5])

# ---------------- 4. 网络函数 ----------------
print("\n### 4. 网络函数 / 远程通信")
NET = r"\b(curl_exec|curl_init|file_get_contents|fsockopen|stream_socket_client|wp_remote_get)\s*\("
h = grep_files(NET, (".php",))
h = [x for x in h if not x[0].startswith("scripts/")]
check("PHP 无网络请求函数", not h, h[:5])
hjs = grep_files(r"\b(fetch\s*\(|XMLHttpRequest|navigator\.sendBeacon)\s*", (".js",))
check("JS 无 fetch / XHR / sendBeacon", not hjs, hjs[:5])

# ---------------- 5. 远程资源引用 ----------------
print("\n### 5. 远程资源引用（外站 script / link / url()）")
h = grep_files(r"""<\s*script[^>]+src\s*=\s*["']https?://""", (".php", ".js", ".css"))
h += grep_files(r"""<\s*link[^>]+href\s*=\s*["']https?://""", (".php", ".js", ".css"))
h += grep_files(r"""url\(\s*["']?https?://""", (".css",))
check("无外站 script/link/url 引用", not h, h[:5])
h = grep_files(r"https?://", (".php",))
# 允许：作者 URL 注释 / 文档链接
allow = [x for x in h if "at8.fun" in x[2] or "instant.page" in x[2] or x[0] in DOC_FILES]
rest = [x for x in h if x not in allow]
soft("PHP 中的 http(s) 仅剩注释/作者/文档链接", not rest, rest[:5])
print("       （共 %d 处 http(s)，其中 %d 处为文档链接）" % (len(h), len(allow)))

# ---------------- 6. 动态包含 ----------------
print("\n### 6. 动态包含（include / require 路径是否可控）")
h = grep_files(r"\b(include|include_once|require|require_once)\b", (".php",))
h = [x for x in h if not x[0].startswith("scripts/")]
dyn = [x for x in h if re.search(r"(include|require)[_a-z]*\s*\(\s*\$", x[2])]
check("无「变量作为 include/require 路径」", not dyn, dyn[:5])
print("       固定路径包含 %d 处：%s" % (len(h), sorted(set(x[0] for x in h))))

# ---------------- 7. 文件操作 ----------------
print("\n### 7. 文件操作")
h = grep_files(r"\b(fopen|file_put_contents|fwrite|unlink|rename|copy|mkdir|rmdir|scandir|glob)\s*\(", (".php",))
h = [x for x in h if not x[0].startswith("scripts/")]
check("PHP 无文件读写/删除操作（File Write = NONE）", not h, h[:5])

# ---------------- 8. SQL ----------------
print("\n### 8. SQL")
h = grep_files(r"\b(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER|CREATE TABLE)\b", (".php",))
h = [x for x in h if not x[0].startswith("scripts/") and "sql" not in x[0]]
h = [x for x in h if not re.search(r"//|/\*|\*", x[2][:20])]
check("PHP 无 SQL 语句（Direct SQL = NONE）", not h, h[:5])

# ---------------- 9. 日志 ----------------
print("\n### 9. 日志与调试输出")
h = grep_files(r"\b(error_log|var_dump|print_r|var_export)\s*\(", (".php",))
h = [x for x in h if not x[0].startswith("scripts/")]
check("无 error_log / var_dump / print_r", not h, h[:5])
hjs = grep_files(r"\bconsole\.(log|debug|info|warn|error)\s*\(", (".js",))
check("JS 无 console.* 输出", not hjs, hjs[:5])

# ---------------- 10. 输出转义 ----------------
print("\n### 10. 前台输出转义")
h = grep_files(r"""echo\s+\$""", (".php",))
unescaped = []
for f, i, line in h:
    if "htmlspecialchars" in line or "json_encode" in line or "(int)" in line:
        continue
    unescaped.append((f, i, line))
check("所有 echo $xxx 均带转义/强制类型转换", not unescaped, unescaped[:5])

# ---------------- 11. CSRF / 权限 ----------------
print("\n### 11. 权限与 CSRF")
check("后台页有 CheckRights", "CheckRights" in rd(os.path.join(ROOT, "main.php")))
check("写操作有 CheckIsRefererValid", "CheckIsRefererValid" in rd(os.path.join(ROOT, "main.php")))
check("表单输出 GetCSRFToken", "GetCSRFToken" in rd(os.path.join(ROOT, "main.php")))

# ---------------- 12. CSS ----------------
print("\n### 12. CSS 规范")
css = rd(os.path.join(ROOT, "style.css"))
check("CSS 无 !important", "!important" not in css)
# 全局裸选择器：行首直接是 button/input/body/a/img/table/div 等
bare = []
for i, line in enumerate(css.splitlines(), 1):
    s = line.strip()
    if not s or s.startswith(("/*", "*", "@", "}", "{")):
        continue
    if re.match(r"^(button|input|textarea|select|body|a|img|table|div|p|h[1-6]|ul|li|form)\b", s):
        bare.append((i, s))
check("CSS 无全局裸选择器污染后台", not bare, bare[:5])
classes = sorted(set(re.findall(r"\.([a-zA-Z][\w-]*)", css)))
bad_cls = [c for c in classes if not c.startswith("at8")]
soft("CSS class 均带 at8 前缀（当前：%s）" % ",".join(classes[:6]), not bad_cls, bad_cls[:6])

# ---------------- 13. JS 命名空间 ----------------
print("\n### 13. JS 命名空间")


def strip_comments(src):
    """剥离块注释与行注释，避免注释文字干扰源码断言"""
    src = re.sub(r"/\*.*?\*/", "", src, flags=re.S)
    src = re.sub(r"(?m)^\s*//.*$", "", src)
    src = re.sub(r"(?<!:)//.*$", "", src)
    return src


for name in ("at8-guard.js", "at8-lazy.js"):
    t = rd(os.path.join(ROOT, "assets", name))
    code = strip_comments(t)
    head = code.strip()
    # IIFE 判定：(function...{...})() 或 !function...{}()
    check("%s 为 IIFE 包裹（不污染全局）" % name,
          bool(re.match(r"^\(?\s*(function|async function|\(\s*\)\s*=>)", head)) or head.startswith("!"),
          head[:40])
    check("%s 使用 'use strict'" % name, "'use strict'" in code)
    # 顶层变量：IIFE 内部的所有声明都在函数作用域内，只需确认没有 IIFE 之外的声明
    tail_iife = head.find("})()")
    outside = (head[tail_iife + 4:] if tail_iife != -1 else "")
    glob = re.findall(r"^\s*(?:var|let|const)\s+\w+", outside, re.M)
    check("%s 无 IIFE 之外的全局变量声明" % name, not glob, glob[:3])
    # 排除 === / == / => 等「= 不是赋值」的情形
    check("%s 无 window.* 赋值" % name, not re.search(r"window\.\w+\s*=(?![=>])", code))
    check("%s 无动态 script 注入" % name, "createElement('script')" not in code and 'createElement("script")' not in code)
    check("%s 无 innerHTML 写入" % name, "innerHTML" not in code)
    check("%s 无 document.write" % name, "document.write" not in code)

# ---------------- 14. 目录污染 ----------------
print("\n### 14. 仓库目录污染")
# 「绝不允许存在」的开发/依赖目录
DIRTY = [".github", "node_modules", "vendor", "test", "tests",
         ".env", "debug", ".idea", ".vscode"]
# 「允许存在、但必须被 zbignore 排除出包」的构建产物目录（规范 §75 要求产出 dist/）
BUILD_OUT = ["dist", "build"]
found = [d for d in DIRTY if os.path.exists(os.path.join(ROOT, d))]
check("无开发/测试/依赖目录（.git 除外）", not found, found)

_ign = []
_igf = os.path.join(ROOT, "zbignore.txt")
if os.path.isfile(_igf):
    _ign = [l.strip().strip("/") for l in rd(_igf).splitlines()
            if l.strip() and not l.strip().startswith("#")]
_bad_out = [d for d in BUILD_OUT
            if os.path.exists(os.path.join(ROOT, d)) and d not in _ign]
check("构建产物目录已列入 zbignore（dist/build 不得进包）", not _bad_out, _bad_out)
_present_out = [d for d in BUILD_OUT if os.path.exists(os.path.join(ROOT, d))]
if _present_out:
    print("       存在且已排除的构建产物目录：%s" % _present_out)

logs = [rel(p) for p in walk((".log",))]
check("无 *.log 文件", not logs, logs[:5])
secrets = [rel(p) for p in walk((".pem", ".key", ".sql", ".p12", ".pfx"))]
check("无密钥/证书/SQL 文件", not secrets, secrets[:5])
for f in (".env", "debug.log", "credentials", "password.txt", "secret.txt", "token.txt"):
    soft("不存在 %s" % f, not os.path.exists(os.path.join(ROOT, f)))

# ---------------- 15. 第三方组件 ----------------
print("\n### 15. 第三方组件")
inst = rd(os.path.join(ROOT, "assets", "instantpage.js"))
m = re.search(r"instant\.page\s+v?([\d]+\.[\d]+\.[\d]+)", inst[:3000])
soft("instant.page 版本可识别", bool(m), ("v" + m.group(1)) if m else "未找到")
if m:
    import hashlib
    dig = hashlib.md5(inst.encode("utf-8")).hexdigest()
    # 与官方上游一致的已知指纹（见 THIRD-PARTY-LICENSES.md）
    soft("instantpage.js 与官方源一致（md5 9b05e316aedabe445698f148c00e9e5e）",
         dig == "9b05e316aedabe445698f148c00e9e5e", dig)
soft("存在 THIRD-PARTY-LICENSES.md", os.path.exists(os.path.join(ROOT, "THIRD-PARTY-LICENSES.md")))
soft("存在 LICENSE", os.path.exists(os.path.join(ROOT, "LICENSE")))

# ---------------- 16. 发布包审计（可选） ----------------
if "--zba" in sys.argv:
    import gzip
    import hashlib

    zp = sys.argv[sys.argv.index("--zba") + 1]
    print("\n### 16. 发布包审计：%s" % os.path.basename(zp))
    raw = open(zp, "rb").read()
    check("gzip 魔数 1F8B", raw[:2] == b"\x1f\x8b", raw[:2].hex())
    x = ET.fromstring(gzip.decompress(raw).decode("utf-8"))
    check("根节点 type=plugin", x.get("type") == "plugin")
    check("<id> == %s" % x_id, x.findtext("id") == x_id)
    check("包内 <version> == %s" % x_ver, x.findtext("version") == x_ver)
    files = [(n.findtext("path"), n.findtext("stream") or "") for n in x.iter("file")]
    print("       包内 %d 个文件：" % len(files))
    for p, s in sorted(files):
        print("         - %s (%d B)" % (p, len(s)))
    BAD_IN = [".git", ".github", "node_modules", ".env", "debug", "test", "tests"]
    bad = [p for p, s in files if any(b in p for b in BAD_IN)]
    check("包内无禁止内容", not bad, bad)
    bad2 = [p for p, s in files if p.endswith((".log", ".sql", ".pem", ".key"))]
    check("包内无日志/密钥/SQL", not bad2, bad2)
    check("包内不含 CHANGELOG.md", not any("CHANGELOG" in p for p, s in files))
    check("包内不含 zbignore.txt", not any("zbignore" in p for p, s in files))
    # 逐字节比对
    import base64
    diff = []
    for p, s in files:
        local = os.path.join(os.path.dirname(zp), "at8_pagespeed", p.split("/", 1)[1])
        if not os.path.exists(local):
            local = os.path.join(ROOT, p.split("/", 1)[1])
        if not os.path.exists(local):
            diff.append(p + " (源文件缺失)")
            continue
        want = open(local, "rb").read()
        got = base64.b64decode(s) if s else b""
        if got != want:
            diff.append(p)
    check("包内文件与源码逐字节一致", not diff, diff)
    print("       包体 %d B  md5=%s" % (len(raw), hashlib.md5(raw).hexdigest()))

# ---------------- 16. PHP 版本语法兼容扫描 ----------------
print("\n### 16. PHP 版本语法兼容扫描（声明下限 7.4，扫描是否使用了更高版本的专有语法）")
# 语法下限扫描：若源码不含任何 PHP 8.0+ 专有语法，则语法层面可下探至 7.4
SYNTAX_80 = [
    (r"\?->", "nullsafe 运算符 ?->"),
    (r"\bmatch\s*\(", "match 表达式"),
    (r"#\[\s*\w+", "PHP 属性 Attribute"),
    (r"\b(str_contains|str_starts_with|str_ends_with)\s*\(", "8.0 字符串函数"),
    (r"\bfunction\s+\w*\s*\([^)]*\b(public|private|protected)\s+\$", "构造器属性提升"),
    (r":\s*(\w+\|)+\w+", "联合类型声明"),
]
SYNTAX_81 = [
    (r"\benum\s+\w+", "enum 枚举"),
    (r"\breadonly\s+", "readonly 修饰符"),
    (r":\s*never\b", "never 返回类型"),
]
SYNTAX_82 = [(r"\breadonly\s+class\b", "readonly class")]
SYNTAX_83 = [(r"\bjson_validate\s*\(", "json_validate()")]
SYNTAX_84 = [(r"\bpublic\s+\w+\s*\{\s*(get|set)\b", "属性钩子 property hooks")]

php_files = [p for p in walk((".php",)) if not rel(p).startswith("scripts/")]
php_src = "\n".join(strip_comments(rd(p)) for p in php_files)
for label, pats in (("8.0", SYNTAX_80), ("8.1", SYNTAX_81), ("8.2", SYNTAX_82),
                    ("8.3", SYNTAX_83), ("8.4", SYNTAX_84)):
    hit = []
    for pat, desc in pats:
        if re.search(pat, php_src):
            hit.append(desc)
    soft("未使用 PHP %s 专有语法" % label, not hit, hit)
print("       扫描 PHP 文件: %s" % ", ".join(rel(p) for p in php_files))

# ---------------- 汇总 ----------------
print("\n" + "=" * 70)
print("汇总：FAIL %d 项 / WARN %d 项" % (len(fail), len(warn)))
if fail:
    for f in fail:
        print("  FAIL: " + f)
if warn:
    for w in warn:
        print("  WARN: " + w)
print("=" * 70)
sys.exit(1 if fail else 0)
