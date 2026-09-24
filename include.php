<?php
/**
 * 页面加速 at8_pagespeed
 *
 * 纯前端输出层优化：只使用官方 Filter_Plugin_Zbp_MakeTemplatetags（1.7.x 前台注入点，
 * 引用传递 $tags，追加 $tags['header'] / $tags['footer']）注入资源，
 * 不注册任何系统业务流程相关的 Hook（不拦截上传 / 删除 / 发布 / 评论等）。
 *
 * 最低 PHP：7.4（plugin.xml 的 <phpver>，安装门槛）。
 * 依据：全量文件在 PHP 7.3.4 通过 php -l（严于 7.4）；运行时在 PHP 8.3.33 实测零报错；
 * 源码未使用任何 PHP 8.0+ 专有语法。低于 7.4 的环境在安装阶段被拦下，不作兼容承诺。
 *
 * 最低 Z-BlogPHP：1.7.5 Build 3510（plugin.xml 的 <adapted>173510</adapted>，安装门槛）。
 * 依据：前台注入依赖官方 Filter_Plugin_Zbp_MakeTemplatetags（引用传递 $tags），
 * 后台菜单依赖 Filter_Plugin_Admin_LeftMenu，配置使用 Config() 单参属性式读写。
 *
 * 边界：本插件只做前台性能优化与「用户自定义的普通链接预加载排除」，
 * 不识别、不拦截、不接管、不修改任何 Z-BlogPHP 系统业务接口。
 *
 * @author 漫步白月光 https://www.at8.fun/
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

define('AT8_PAGESPEED_VERSION', '1.0.9');

RegisterPlugin('at8_pagespeed', 'ActivePlugin_at8_pagespeed');

/**
 * 挂载官方过滤器：
 * - MakeTemplatetags：前台模板标签构建时追加 header / footer 输出（1.7.x 官方前台注入点）
 * - Admin_LeftMenu：后台左侧菜单
 * 两者都只是「追加输出」与「加菜单」，不介入任何业务流程。
 */
function ActivePlugin_at8_pagespeed()
{
    Add_Filter_Plugin('Filter_Plugin_Zbp_MakeTemplatetags', 'at8_pagespeed_tags');
    Add_Filter_Plugin('Filter_Plugin_Admin_LeftMenu', 'at8_pagespeed_menu');
}

/**
 * 后台左侧菜单入口（仅 Admin 权限可见）
 */
function at8_pagespeed_menu(&$m)
{
    global $zbp;
    $m['nav_at8_pagespeed'] = MakeLeftMenu(
        'admin',
        '页面加速',
        $zbp->host . 'zb_users/plugin/at8_pagespeed/main.php',
        'nav_at8_pagespeed',
        'nav_at8_pagespeed',
        $zbp->host . 'zb_users/plugin/at8_pagespeed/logo.png'
    );
}

/**
 * 是否前台输出（后台 / 登录页一律不注入前台优化脚本）
 *
 * 这是插件自身「输出范围」的控制，不是对链接或系统接口的识别、拦截或接管：
 * 1) $zbp->ismanage：后台请求由系统置 true（官方原生标记）；
 * 2) 请求脚本位于系统后台目录下时同样不注入。
 */
function at8_pagespeed_is_frontend()
{
    global $zbp;

    if (isset($zbp->ismanage) && $zbp->ismanage) {
        return false;
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
    if ($script !== '' && strpos($script, '/zb_system/') !== false) {
        return false;
    }

    return true;
}

/**
 * 配置默认值（键 => 默认值，含类型约定）
 * 同时供配置读取、安装补齐、升级迁移三处复用，避免默认值分散漂移
 *
 * @return array
 */
function at8_pagespeed_defaults()
{
    return array(
        'preload_enabled' => 1,   // int：instant.page 悬停预加载开关
        'preload_delay'   => 65,  // int：悬停触发延迟（毫秒，0~2000）
        // string：预加载黑名单关键字（每行一个）。
        // 这是一组**通用**的 URL 关键词，只用于「链接地址包含该关键词时不预加载」，
        // 不代表、也不针对任何 Z-BlogPHP 系统业务接口。用户可在后台自行增删。
        // 是否预加载的最终判定由 instant.page 自身规则负责（例如其默认不预加载带
        // query string 的链接），本插件只是在此之上叠加一层用户自定义的普通关键字排除。
        'blacklist'       => "logout\nlogin\ncart\ncheckout\npay\norder\nfeed",
        'lazy_enabled'    => 1,   // int：图片 / iframe 懒加载开关
        'lazy_skip'       => 2,   // int：跳过前 N 张图（保 LCP，0~20）
        'dns_domains'     => '',  // string：附加 DNS 预取域名（每行一个，空 = 仅站点自身）
    );
}

/**
 * 归一化单个 DNS 预取域名：去协议、去路径，再校验域名格式。
 *
 * 合法返回域名（如 example.com），不合法返回空串。
 * 保存时与输出时都调用它（纵深防御）：库里不落脏数据，输出也不可能带出脏数据。
 *
 * @param string $d 原始输入
 * @return string
 */
function at8_pagespeed_normalize_domain($d)
{
    $d = trim((string) $d);
    if ($d === '') {
        return '';
    }
    $d = preg_replace('#^https?://#i', '', $d);      // 去协议
    $d = trim(preg_replace('@[/?#].*$@', '', $d));   // 去路径 / 查询 / 锚点
    if ($d === '' || strlen($d) > 253) {
        return '';
    }
    if (!preg_match('/^[a-z0-9][a-z0-9.-]*(\.[a-z0-9.-]+)+$/i', $d)) {
        return '';
    }
    return $d;
}

/**
 * 配置读取（带默认值；1.7.5 Config 为属性式读写，$zbp->Config($name) 请求内缓存）
 *
 * @param string $key 配置键
 * @return mixed
 */
function at8_pagespeed_cfg($key)
{
    global $zbp;
    $defaults = at8_pagespeed_defaults();
    $c = $zbp->Config('at8_pagespeed');
    $v = isset($c->{$key}) ? $c->{$key} : null;
    if ($v === null || $v === '') {
        return isset($defaults[$key]) ? $defaults[$key] : null;
    }

    return $v;
}

/**
 * 前台模板标签注入（官方 Filter_Plugin_Zbp_MakeTemplatetags，引用传递）
 * header：DNS 预取；footer：instant.page + 懒加载脚本（均本站内置资源，无外站引用）
 */
function at8_pagespeed_tags(&$tags)
{
    global $zbp;

    if (!at8_pagespeed_is_frontend()) {
        return;
    }
    if (!isset($tags['header']) || !isset($tags['footer'])) {
        return;
    }

    $v = AT8_PAGESPEED_VERSION;

    // ---- header：DNS 预取 ----
    $head = "\r\n<!-- at8_pagespeed " . $v . " -->\r\n";
    $head .= '<meta http-equiv="x-dns-prefetch-control" content="on">' . "\r\n";

    $host = preg_replace('#^https?://#i', '', $zbp->host);
    $host = rtrim($host, '/');
    $head .= '<link rel="dns-prefetch" href="' . htmlspecialchars($host, ENT_QUOTES, 'UTF-8') . '">' . "\r\n";

    // 后台附加域名（每行一个，自动去协议防注入）
    $domains = at8_pagespeed_cfg('dns_domains');
    if (is_string($domains) && $domains !== '') {
        $arr = preg_split('/[\r\n]+/', $domains);
        foreach ($arr as $d) {
            // 与保存时共用同一套归一化规则（输出再做一次，防历史脏数据）
            $d = at8_pagespeed_normalize_domain($d);
            if ($d === '') {
                continue;
            }
            $head .= '<link rel="dns-prefetch" href="' . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . '">' . "\r\n";
        }
    }
    $tags['header'] .= $head;

    // ---- footer：脚本注入 ----
    $base = $zbp->host . 'zb_users/plugin/at8_pagespeed/assets/';
    $foot = "\r\n<!-- at8_pagespeed -->\r\n";

    // 懒加载
    if ((int) at8_pagespeed_cfg('lazy_enabled') === 1) {
        $skip = max(0, min(20, (int) at8_pagespeed_cfg('lazy_skip')));
        $foot .= '<script>window.at8PsLazySkip=' . $skip . ';</script>' . "\r\n";
        $foot .= '<script src="' . $base . 'at8-lazy.js?v=' . $v . '" defer></script>' . "\r\n";
    }

    // instant.page 悬停预加载（文件内置，不引用外站）
    if ((int) at8_pagespeed_cfg('preload_enabled') === 1) {
        $delay = max(0, min(2000, (int) at8_pagespeed_cfg('preload_delay')));

        /*
         * 【关键】instant.page v5 的触发延迟读取自 document.body 的 data-instant-intensity，
         * 而不是 <script> 标签上的属性（那是 v4 的用法，v5 已改）。
         * 源码依据（内置文件 assets/instantpage.js）：
         *   init() 内 `if ('instantIntensity' in document.body.dataset) { ... _delayOnHover = intensityAsInteger }`。
         * 因此必须在其执行前把这个属性写到 body 上；若写在 script 标签上则完全无人读取，
         * 后台的「触发延迟」设置会静默失效、始终走内置默认 65ms。
         * 用同步内联脚本在解析期写入（footer 位于 body 内，此时 document.body 已存在），
         * 不依赖任何外部文件是否加载成功；at8-guard.js 内再做一次兜底。
         */
        $foot .= '<script>(function(){var b=document.body;if(b){b.setAttribute("data-instant-intensity","'
            . $delay . '");}})();</script>' . "\r\n";

        // 用户配置的黑名单关键字传给守卫脚本：命中链接打 data-no-instant，预加载自动跳过。
        // 只是普通字符串关键字匹配，不做任何系统接口识别。
        $arr = at8_pagespeed_blacklist_lines(at8_pagespeed_cfg('blacklist'));
        // HEX_* 标志：黑名单关键字含 `</script>` / `<!--` 时不会 breakout `<script>` 上下文；
        // JSON_UNESCAPED_UNICODE 保留中文原字符（黑名单为管理员自配，无需过度转义）
        $foot .= '<script>window.at8PsBlacklist=' . json_encode($arr, JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . ';window.at8PsPreloadDelay=' . $delay . ';</script>' . "\r\n";
        $foot .= '<script src="' . $base . 'at8-guard.js?v=' . $v . '" defer></script>' . "\r\n";
        $foot .= '<script src="' . $base . 'instantpage.js?v=' . $v . '" defer></script>' . "\r\n";
    }

    $tags['footer'] .= $foot;
}

/**
 * 把用户配置的黑名单文本解析成关键字数组
 *
 * 这是纯粹的通用文本解析，不含任何业务语义：
 *   只做「按行拆分 → 去空行 → 去首尾空白 → 长度截断 → 大小写不敏感去重」。
 * 不判断某个关键字是否属于某个系统的接口，也不强制追加任何条目。
 *
 * @param string $text 黑名单原文（每行一个关键字）
 * @return array
 */
function at8_pagespeed_blacklist_lines($text)
{
    $arr = array();
    $seen = array();

    if (!is_string($text) || $text === '') {
        return $arr;
    }

    foreach (preg_split('/[\r\n]+/', $text) as $l) {
        $l = trim($l);
        if ($l === '' || strlen($l) > 100) {
            continue;
        }
        $k = strtolower($l);
        if (!isset($seen[$k])) {
            $seen[$k] = true;
            $arr[] = $l;
        }
    }

    return $arr;
}

/**
 * 配置迁移：按 ConfigVer 逐级升级，可重复调用（幂等）
 *
 * 调用点有三处，覆盖已知的全部升级路径：
 *   ① InstallPlugin_at8_pagespeed()  —— 安装 / 停用后再启用
 *   ② UpdatePlugin_at8_pagespeed()   —— 官方升级钩子
 *   ③ main.php（进入设置页时）        —— 手动覆盖文件升级时 ①② 都不会触发，由这里补
 * （Z-BlogPHP 核心没有 UpdatePlugin() 函数，②实际由后台页面的 `misc&type=updatedapp`
 *  路由触发，是否执行取决于管理员是否进后台，所以 ③ 是必要的补充路径。）
 *
 * 【1.0.9 起的迁移原则】
 *   1. 只补齐缺失的配置键；
 *   2. 不再向黑名单新增任何关键字；
 *   3. 不再恢复、不再补齐、不再输出层注入任何系统专用关键字；
 *   4. 用户已有的黑名单一律原样保留——无法可靠区分「插件自动加入」与「用户自己填写」
 *      时，宁可保留历史配置，也不做任何猜测性删除。
 *
 * @return void
 */
function at8_pagespeed_migrate_config()
{
    global $zbp;
    $c = $zbp->Config('at8_pagespeed');
    // 用 isset 取值：老站点可能从未写入 ConfigVer，直接读未定义属性会触发告警
    $ver = isset($c->ConfigVer) ? (int) $c->ConfigVer : 0;
    $dirty = false;

    if ($ver < 1) {
        // v1：补齐默认键（含 1.0.0 未写入 ConfigVer 的场景）
        foreach (at8_pagespeed_defaults() as $k => $v) {
            if (!isset($c->{$k})) {
                $c->{$k} = $v;
            }
        }
        $ver = 1;
        $dirty = true;
    }

    // v2 → v3：只推进版本号，不动黑名单。
    // 历史版本曾在此向黑名单追加系统专用关键字，该行为自 1.0.9 起已完全取消。
    if ($ver < 3) {
        $ver = 3;
        $dirty = true;
    }

    $c->ConfigVer = $ver;
    if ($dirty) {
        $zbp->SaveConfig('at8_pagespeed');
    }
}

/**
 * 安装插件：写入默认配置
 *
 * 幂等：仅补齐缺失的配置键，不覆盖用户已保存的值（重复安装 / 停用后再启用均安全）。
 * 1.7.5 配置为属性式读写（$zbp->Config($name) 单参，返回 Config 对象）。
 */
function InstallPlugin_at8_pagespeed()
{
    global $zbp;
    $c = $zbp->Config('at8_pagespeed');

    foreach (at8_pagespeed_defaults() as $k => $v) {
        if (!isset($c->{$k})) {
            $c->{$k} = $v;
        }
    }
    if (!isset($c->ConfigVer)) {
        $c->ConfigVer = 1;   // 起始版本；随后由 migrate 逐级升到当前版本
    }
    $zbp->SaveConfig('at8_pagespeed');

    at8_pagespeed_migrate_config();
}

/**
 * 版本升级：按 ConfigVer 逐级迁移配置
 * 后续版本若新增/变更配置键，在 at8_pagespeed_migrate_config() 中追加分支，
 * 不做无谓的数据重建
 */
function UpdatePlugin_at8_pagespeed()
{
    at8_pagespeed_migrate_config();
}

/**
 * 旧版更新钩子命名兼容（1.7 前）
 */
function at8_pagespeed_Updated()
{
    UpdatePlugin_at8_pagespeed();
}

/**
 * 停用 / 卸载钩子
 *
 * 【1.7.5 实机核实，勿改】官方 DisablePlugin() 内部会调用 UninstallPlugin_xxx()——
 * 也就是「停用插件」同样会进入本函数；真正的「删除应用」由 AppCentre/app_del.php
 * 直接删除插件目录，反而不触发本函数。
 * 因此这里绝不能删除配置：一旦删除，用户每次停用都会丢失全部自定义设置（数据丢失）。
 * 配置行保留为站点级偏好，重新安装后自动沿用；如需彻底清除，手动删除 zbp_config 中
 * conf_Name = at8_pagespeed 的记录即可。本插件不建表、不写文件，无其他数据需要清理。
 */
function UninstallPlugin_at8_pagespeed()
{
    // 有意为空实现，理由见上方说明
}
