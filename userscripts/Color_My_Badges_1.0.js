// ==UserScript==
//
// @name         Color My Badges
// @description  A simple script to show color badges regardless of unlocks on the Retro Achievements website.
//
// @version      1.0
// @copyright    MIT license
//
// @author       clymax
// @homepage     https://retroachievements.org/user/clymax
//
// @namespace    RA
// @match        https://retroachievements.org/*
// @icon         https://i.imgur.com/jFU9k3y.png
// @grant        none
// @noframes
//
// ==/UserScript==

(function() {
    document.querySelectorAll('img[src$=".png"]').forEach(item => {item.src = item.src.replace('_lock', '')} );
})();
