/**
 * at8_pagespeed · 预加载守卫
 * 给命中黑名单关键字的链接打 data-no-instant 标记，
 * instant.page 看到该标记即跳过预加载（退出登录、购物车、删除等敏感操作零干扰）。
 */
(function () {
	'use strict';

	var list = (typeof window.at8PsBlacklist === 'object' && window.at8PsBlacklist.length)
		? window.at8PsBlacklist : [];
	if (!list.length) return;

	function guard(root) {
		var anchors = root.querySelectorAll('a[href]');
		for (var i = 0; i < anchors.length; i++) {
			var a = anchors[i];
			if (a.hasAttribute('data-no-instant')) continue;
			var href = a.getAttribute('href') || '';
			if (href.charAt(0) === '#') continue;
			for (var j = 0; j < list.length; j++) {
				if (href.indexOf(list[j]) !== -1) {
					a.setAttribute('data-no-instant', '');
					break;
				}
			}
		}
	}

	function run() {
		guard(document);
		// 动态插入的链接（评论翻页、无限滚动等）兜底监听
		if (typeof MutationObserver !== 'undefined') {
			var mo = new MutationObserver(function (muts) {
				for (var k = 0; k < muts.length; k++) {
					var nodes = muts[k].addedNodes;
					for (var n = 0; n < nodes.length; n++) {
						var node = nodes[n];
						if (node.nodeType === 1) {
							if (node.tagName === 'A') guard(node.parentNode || document);
							else if (node.querySelectorAll) guard(node);
						}
					}
				}
			});
			mo.observe(document.documentElement, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();
