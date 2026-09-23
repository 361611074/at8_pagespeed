/**
 * at8_pagespeed · 图片/iframe 懒加载
 * 无依赖、无外站资源；给视口外资源补 loading=lazy + decoding=async。
 * 支持 data-no-lazy 排除单个元素。
 */
(function () {
	'use strict';

	var SKIP = (typeof window.at8PsLazySkip === 'number') ? window.at8PsLazySkip : 2;

	function eligible(el) {
		if (el.hasAttribute('data-no-lazy')) return false;
		if (el.hasAttribute('loading')) return false;
		if (el.closest('[data-no-lazy]')) return false;
		// 跳过不可见元素（display:none 的模板/轮播备用图）
		return true;
	}

	function apply() {
		var media = document.querySelectorAll('img, iframe');
		var seen = 0;
		for (var i = 0; i < media.length; i++) {
			var el = media[i];
			if (!eligible(el)) continue;
			// 头部 N 张保持原样（保首屏 LCP）
			if (el.tagName === 'IMG') {
				seen++;
				if (seen <= SKIP) continue;
			}
			// 已在首屏视口内的不懒加载
			var r = el.getBoundingClientRect();
			if (r.top < window.innerHeight && r.bottom > 0) continue;
			el.setAttribute('loading', 'lazy');
			if (el.tagName === 'IMG') {
				el.setAttribute('decoding', 'async');
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', apply);
	} else {
		apply();
	}
})();
