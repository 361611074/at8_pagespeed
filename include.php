<?php
/**
 * 页面加速 at8_pagespeed
 *
 * 纯前端输出层优化：只使用官方 Filter_Plugin_Zbp_Header / Filter_Plugin_Zbp_Footer
 * 输出过滤器注入资源，不接管、不修改系统任何业务流程（上传 / 删除 / 发布等一概不碰）。
 *
 * @author 漫步白月光 https://www.at8.fun/
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

define('AT8_PAGESPEED_VERSION', '1.0.0');

RegisterPlugin('at8_pagespeed', 'ActivePlugin_at8_pagespeed');

/**
 * 挂载官方过滤器：
 * - MakeTemplatetags：前台模板标签构建时追加 header / footer 输出（1.7.5 官方唯一前台注入点）
 * - Admin_LeftMenu：后台左侧菜单
 * 不接管、不修改系统任何业务流程（上传 / 删除 / 发布等一概不碰）。
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
 * 是否前台输出（后台 / 登录页 / 接口请求一律不注入）
 */
function at8_pagespeed_is_frontend()
{
    global $zbp;

    if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
        return false;
    }
    // c_system_admin.php 在所有后台请求开头置 true，前台请求不会加载该文件
    if (isset($zbp->ismanage) && $zbp->ismanage) {
        return false;
    }

    return true;
}

/**
 * 配置读取（带默认值；1.7.5 Config 为属性式读写）
 *
 * @param string $key 配置键
 * @return mixed
 */
function at8_pagespeed_cfg($key)
{
    global $zbp;
    $defaults = array(
        'preload_enabled' => 1,   // instant.page 悬停预加载
        'preload_delay'   => 65,  // 悬停触发延迟（毫秒）
        'blacklist'       => "logout\nlogin\nwp-admin\nadmin\nadmin_\ncart\ncheckout\npay\norder\ndelete\nremove\nedit\n?t=\nfeed",
        'lazy_enabled'    => 1,   // 图片 / iframe 懒加载
        'lazy_skip'       => 2,   // 跳过前 N 张图（保 LCP）
        'dns_domains'     => '',  // 附加 DNS 预取域名（每行一个）
    );
    $c = $zbp->Config('at8_pagespeed');
    $v = isset($c->{$key}) ? $c->{$key} : null;
    if ($v === null || $v === '') {
        return $defaults[$key];
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
            $d = trim($d);
            if ($d === '') {
                continue;
            }
            // 只保留合法域名形态：去协议、去路径，剩余部分须为域名格式
            $d = preg_replace('#^https?://#i', '', $d);
            $d = trim(preg_replace('@[/?#].*$@', '', $d));
            if ($d === '' || !preg_match('/^[a-z0-9][a-z0-9.-]*(\.[a-z0-9.-]+)+$/i', $d) || strlen($d) > 253) {
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
        $intensity = ($delay == 65) ? '' : ' data-instant-intensity="' . $delay . '"';
        // 黑名单关键字传给守卫脚本：命中链接打 data-no-instant，预加载自动跳过
        $bl = at8_pagespeed_cfg('blacklist');
        $arr = array();
        if (is_string($bl) && $bl !== '') {
            foreach (preg_split('/[\r\n]+/', $bl) as $l) {
                $l = trim($l);
                if ($l !== '' && strlen($l) <= 100) {
                    $arr[] = $l;
                }
            }
        }
        $foot .= '<script>window.at8PsBlacklist=' . json_encode($arr, JSON_UNESCAPED_UNICODE) . ';</script>' . "\r\n";
        $foot .= '<script src="' . $base . 'at8-guard.js?v=' . $v . '" defer></script>' . "\r\n";
        $foot .= '<script src="' . $base . 'instantpage.js?v=' . $v . '" defer' . $intensity . '></script>' . "\r\n";
    }

    $tags['footer'] .= $foot;
}

/**
 * 安装插件：写入默认配置（属性式写，官方用法）
 */
function InstallPlugin_at8_pagespeed()
{
    global $zbp;
    $c = $zbp->Config('at8_pagespeed');
    $c->preload_enabled = at8_pagespeed_cfg('preload_enabled');
    $c->preload_delay = at8_pagespeed_cfg('preload_delay');
    $c->blacklist = at8_pagespeed_cfg('blacklist');
    $c->lazy_enabled = at8_pagespeed_cfg('lazy_enabled');
    $c->lazy_skip = at8_pagespeed_cfg('lazy_skip');
    $c->dns_domains = at8_pagespeed_cfg('dns_domains');
    $zbp->SaveConfig('at8_pagespeed');
}

/**
 * 卸载插件：仅清理本插件配置，不动任何站点数据
 */
function UninstallPlugin_at8_pagespeed()
{
    global $zbp;
    $zbp->DelConfig('at8_pagespeed');
}
