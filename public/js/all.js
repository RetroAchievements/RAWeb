
/*
 Watermark v3.1.4 (August 13, 2012) plugin for jQuery
 http://jquery-watermark.googlecode.com/
 Copyright (c) 2009-2012 Todd Northrop
 http://www.speednet.biz/
 Dual licensed under the MIT or GPL Version 2 licenses.
 */
(function(n, t, i){var g = "TEXTAREA", d = "function", nt = "password", c = "maxLength", v = "type", r = "", u = !0, rt = "placeholder", h = !1, tt = "watermark", s = tt, o = "watermarkClass", w = "watermarkFocus", a = "watermarkSubmit", b = "watermarkMaxLength", e = "watermarkPassword", f = "watermarkText", l = /\r/g, ft = /^(button|checkbox|hidden|image|radio|range|reset|submit)$/i, it = "input:data(" + s + "),textarea:data(" + s + ")", p = ":watermarkable", k = ["Page_ClientValidate"], y = h, ut = rt in document.createElement("input"); n.watermark = n.watermark || {version:"3.1.4", runOnce:u, options:{className:tt, useNative:u, hideBeforeUnload:u}, hide:function(t){n(t).filter(it).each(function(){n.watermark._hide(n(this))})}, _hide:function(n, i){var a = n[0], w = (a.value || r).replace(l, r), h = n.data(f) || r, p = n.data(b) || 0, y = n.data(o), s, u; h.length && w == h && (a.value = r, n.data(e) && (n.attr(v) || r) === "text" && (s = n.data(e) || [], u = n.parent() || [], s.length && u.length && (u[0].removeChild(n[0]), u[0].appendChild(s[0]), n = s)), p && (n.attr(c, p), n.removeData(b)), i && (n.attr("autocomplete", "off"), t.setTimeout(function(){n.select()}, 1))), y && n.removeClass(y)}, show:function(t){n(t).filter(it).each(function(){n.watermark._show(n(this))})}, _show:function(t){var p = t[0], g = (p.value || r).replace(l, r), i = t.data(f) || r, k = t.attr(v) || r, d = t.data(o), h, s, a; g.length != 0 && g != i || t.data(w)?n.watermark._hide(t):(y = u, t.data(e) && k === nt && (h = t.data(e) || [], s = t.parent() || [], h.length && s.length && (s[0].removeChild(t[0]), s[0].appendChild(h[0]), t = h, t.attr(c, i.length), p = t[0])), (k === "text" || k === "search") && (a = t.attr(c) || 0, a > 0 && i.length > a && (t.data(b, a), t.attr(c, i.length))), d && t.addClass(d), p.value = i)}, hideAll:function(){y && (n.watermark.hide(p), y = h)}, showAll:function(){n.watermark.show(p)}}, n.fn.watermark = n.fn.watermark || function(i, y){var tt = "string"; if (!this.length)return this; var k = h, b = typeof i == tt; return b && (i = i.replace(l, r)), typeof y == "object"?(k = typeof y.className == tt, y = n.extend({}, n.watermark.options, y)):typeof y == tt?(k = u, y = n.extend({}, n.watermark.options, {className:y})):y = n.watermark.options, typeof y.useNative != d && (y.useNative = y.useNative?function(){return u}:function(){return h}), this.each(function(){var et = "dragleave", ot = "dragenter", ft = this, h = n(ft), st, d, tt, it; if (h.is(p)){if (h.data(s))(b || k) && (n.watermark._hide(h), b && h.data(f, i), k && h.data(o, y.className)); else{if (ut && y.useNative.call(ft, h) && (h.attr("tagName") || r) !== g){b && h.attr(rt, i); return}h.data(f, b?i:r), h.data(o, y.className), h.data(s, 1), (h.attr(v) || r) === nt?(st = h.wrap("<span>").parent(), d = n(st.html().replace(/type=["']?password["']?/i, 'type="text"')), d.data(f, h.data(f)), d.data(o, h.data(o)), d.data(s, 1), d.attr(c, i.length), d.focus(function(){n.watermark._hide(d, u)}).bind(ot, function(){n.watermark._hide(d)}).bind("dragend", function(){t.setTimeout(function(){d.blur()}, 1)}), h.blur(function(){n.watermark._show(h)}).bind(et, function(){n.watermark._show(h)}), d.data(e, h), h.data(e, d)):h.focus(function(){h.data(w, 1), n.watermark._hide(h, u)}).blur(function(){h.data(w, 0), n.watermark._show(h)}).bind(ot, function(){n.watermark._hide(h)}).bind(et, function(){n.watermark._show(h)}).bind("dragend", function(){t.setTimeout(function(){n.watermark._show(h)}, 1)}).bind("drop", function(n){var i = h[0], t = n.originalEvent.dataTransfer.getData("Text"); (i.value || r).replace(l, r).replace(t, r) === h.data(f) && (i.value = t), h.focus()}), ft.form && (tt = ft.form, it = n(tt), it.data(a) || (it.submit(n.watermark.hideAll), tt.submit?(it.data(a, tt.submit), tt.submit = function(t, i){return function(){var r = i.data(a); n.watermark.hideAll(), r.apply?r.apply(t, Array.prototype.slice.call(arguments)):r()}}(tt, it)):(it.data(a, 1), tt.submit = function(t){return function(){n.watermark.hideAll(), delete t.submit, t.submit()}}(tt))))}n.watermark._show(h)}})}, n.watermark.runOnce && (n.watermark.runOnce = h, n.extend(n.expr[":"], {data:n.expr.createPseudo?n.expr.createPseudo(function(t){return function(i){return!!n.data(i, t)}}):function(t, i, r){return!!n.data(t, r[3])}, watermarkable:function(n){var t, i = n.nodeName; return i === g?u:i !== "INPUT"?h:(t = n.getAttribute(v), !t || !ft.test(t))}}), function(t){n.fn.val = function(){var u = this, e = Array.prototype.slice.call(arguments), o; return u.length?e.length?(t.apply(u, e), n.watermark.show(u), u):u.data(s)?(o = (u[0].value || r).replace(l, r), o === (u.data(f) || r)?r:o):t.apply(u):e.length?u:i}}(n.fn.val), k.length && n(function(){for (var u, r, i = k.length - 1; i >= 0; i--)u = k[i], r = t[u], typeof r == d && (t[u] = function(t){return function(){return n.watermark.hideAll(), t.apply(null, Array.prototype.slice.call(arguments))}}(r))}), n(t).bind("beforeunload", function(){n.watermark.options.hideBeforeUnload && n.watermark.hideAll()}))})(jQuery, window);
        $.fn.exists = function () {
        return this.length !== 0;
        }

/* creates an XMLHttpRequest instance */
function createXmlHttpRequestObject()
        {
// will store the reference to the XMLHttpRequest object
        var xmlHttp;
                // this should work for all browsers except IE6 and older
                try
                {
// try to create XMLHttpRequest object
                xmlHttp = new XMLHttpRequest();
                        }
        catch (e)
                {
// assume IE6 or older
                var XmlHttpVersions = new Array("MSXML2.XMLHTTP.6.0",
                        "MSXML2.XMLHTTP.5.0",
                        "MSXML2.XMLHTTP.4.0",
                        "MSXML2.XMLHTTP.3.0",
                        "MSXML2.XMLHTTP",
                        "Microsoft.XMLHTTP");
                        // try every prog id until one works
                        for (var i = 0; i < XmlHttpVersions.length && !xmlHttp; i++)
                        {
                        try
                                {
// try to create XMLHttpRequest object
                                xmlHttp = new ActiveXObject(XmlHttpVersions[i]);
                                        }
                        catch (e) {}
                        }
                }
// return the created object or display an error message
        if (!xmlHttp)
                alert("Error creating the XMLHttpRequest object.");
                else
                return xmlHttp;
                }

//Math.max(b,Math.min(c,a));}
//(function(){Math.clamp=function(a,b,c){return })();

function RA_ReadCookie(name) {
var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
}
return null;
        }

var shortMonths = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
        function insertEditForm(activityVar, articleType)
        {
        var user = RA_ReadCookie('RA_User');
                if (user !== null)
        {
        var rowID = "comment_" + activityVar;
                //alert( rowID );
                var commentRow = $("body").find("#" + rowID);
                if (!commentRow.exists())
        {
        //alert( user );
        //alert( activityVar );
        var userImage = "<img id=\"badgeimg\" src=\"/UserPic/" + user + ".png\" width=32 height=32>";
                var formStr = "";
                formStr += "<textarea id=\"commentTextarea\" rows=2 cols=36 name=\"c\" maxlength=250></textarea>";
                formStr += "&nbsp;";
                formStr += "<img id=\"submitButton\" src=\"http://i.retroachievements.org/Images/Submit.png\" alt=\"Submit\" style=\"cursor: pointer;\" onclick=\"processComment( '" + activityVar + "', '" + articleType + "' )\">";
                var d = new Date();
                var dateStr = "";
                dateStr += d.getDate();
                dateStr += " ";
                dateStr += shortMonths[d.getMonth()];
                dateStr += "<br>";
                dateStr += ("0" + d.getUTCHours()).slice( - 2);
                dateStr += ":";
                dateStr += ("0" + d.getUTCMinutes()).slice( - 2);
                var editRow = "<tr id=" + rowID + "><td class=\"smalldate\">" + dateStr + "</td><td class=\"iconscomment\" colspan=\"2\">" + userImage + "</td><td colspan=\"3\">" + formStr + "</td></tr>";
                var lastComment = $("body").find("#" + activityVar);
                //	Insert this AFTER the last comment for this article.
                while (1)
        {
        var nextComment = lastComment.next();
                if (nextComment.hasClass("feed_comment"))
                lastComment = lastComment.next();
                else
                break;
        }

        lastComment.after(editRow);
                var insertedRow = lastComment.next("tr");
                var commentTextarea = insertedRow.find("#commentTextarea");
                commentTextarea.focus();
                commentTextarea.val("");
                commentTextarea.watermark('Enter a comment here...');
        }
        else
        {
        commentRow.remove();
        }
        }
        }

function processComment(activityVar, articleType)
        {
        var user = RA_ReadCookie('RA_User');
                if (user !== null)
                {
                var rowID = "comment_" + activityVar;
                        //alert( rowID );
                        var commentRow = $("body").find("#" + rowID);
                        if (commentRow.exists())
                        {
                        var textBox = commentRow.find("#commentTextarea");
                                if (textBox.exists())
                                {
                                var comment = textBox.val();
                                        if (comment.length > 0)
                                        {
//var safeComment = comment;	//	Removed!
                                        var safeComment = strip_tags(comment);
                                                //var safeComment = comment.replace( /<|>/g, '_' );
                                                //alert( comment );
                                                //alert( safeComment );

                                                //	Note: using substr on activityVar, because it will be in the format art_213 etc.
                                                var posting = $.post("/requestpostcomment.php", { u: user, a: activityVar.substr(4), c: safeComment, t: articleType });
                                                posting.done(onCommentSuccess);
                                                var submitButton = commentRow.find("#submitButton");
                                                if (submitButton.exists())
                                                {
                                                submitButton.attr("src", "http://i.retroachievements.org/Images/loading.gif"); //	Change to 'loading' gif
                                                        submitButton.attr("onclick", ""); //	stop being able to click this
                                                        submitButton.css("cursor", ""); //	stop being able to see a finger pointer
                                                        }

//textBox.hide();
                                        }
                                else
                                        {
                                        alert("Comment is empty");
                                                }
                                }
                        else
                                {
                                alert("Cannot find textBox #commentTextarea");
                                        }
                        }
                else
                        {
                        alert("Cannot find commentRow" + "#" + rowID);
                                }
                }
        }

function onCommentSuccess(data)
        {
        if (data.substring(0, 6) == "FAILED")
                {
                alert("Failed to post comment! Please try again, or later. Sorry!");
                        return;
                        }

        var sPath = window.location.pathname;
                if (sPath.substr(0, 5).toLowerCase() == '/game')
                {
                location.reload();
                        return;
                        }
        else if (sPath.substr(0, 12).toLowerCase() == "/achievement")
                {
                location.reload();
                        return;
                        }
        else if (sPath.substr(0, 5).toLowerCase() == "/user")
                {
                location.reload();
                        return;
                        }

        var commentRow = $("body").find("#comment_art_" + data);
                if (commentRow.exists())
                {
//	Embed as proper comment instead!
                commentRow.addClass("feed_comment");
                        commentRow.removeAttr("id");
                        var textBox = commentRow.find("#commentTextarea");
                        if (textBox.exists())
                        {
                        var comment = textBox.val();
                                //var safeComment = comment.replace( /<|>/g, '_' );
                                var safeComment = strip_tags(comment);
                                //var safeComment = comment;	//	Removed!

                                textBox.after(safeComment);
                                //	Set its container to commenttext
                                textBox.parent().addClass("commenttext");
                                textBox.remove();
                                }

                var submitButton = commentRow.find("#submitButton");
                        if (submitButton.exists())
                        {
                        submitButton.remove();
                                }
                }
        }

function GetParameterByName(name)
        {
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
                var regexS = "[\\?&]" + name + "=([^&#]*)";
                var regex = new RegExp(regexS);
                var results = regex.exec(window.location.search);
                if (results == null)
                return "";
                else
                return decodeURIComponent(results[1].replace(/\+/g, " "));
                }

function FocusOnArticleID(id)
        {
        $('#art_' + id).scrollIntoView();
                //var x = document.getElementById( "act_" + id );
                //if( x ) { x.scrollIntoView(); }
                }

function UpdateDisplayOrder(user, objID)
        {
        var inputText = $("body").find("#" + objID).val();
                var inputNum = Math.max(0, Math.min(Number(inputText), 10000));
                var posting = $.post("requestupdateachievement.php", { u: user, a: objID.substr(4), f: 1, v: inputNum });
                posting.done(onUpdateDisplayOrderComplete);
                $("body").find("#warning").html("Status: updating...");
                }

function onUpdateDisplayOrderComplete(data)
        {
//alert( data );
        if (data !== "OK")
                {
                $("body").find("#warning").html("Status: Errors..." + data);
                        //alert( data );
                        }
        else
                {
                $("body").find("#warning").html("Status: OK!");
                        }
        }

function strip_tags(html)
        {
//PROCESS STRING
        if (arguments.length < 3)
                {
                html = html.replace(/<\/?(?!\!)[^>]*>/gi, '');
                        }
        else
                {
                var allowed = arguments[1];
                        var specified = eval("[" + arguments[2] + "]");
                        if (allowed){
                var regex = '</?(?!(' + specified.join('|') + '))\b[^>]*>';
                        html = html.replace(new RegExp(regex, 'gi'), '');
                        } else{
                var regex = '</?(' + specified.join('|') + ')\b[^>]*>';
                        html = html.replace(new RegExp(regex, 'gi'), '');
                        }
                }

        return html;
                }

/* Pads a number with 0s */
function pad(n, width, z)
        {
        z = z || '0';
                n = n + '';
                return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
                }

function injectphpbb(start, end)
        {
        var commentTextarea = document.getElementById('commentTextarea');
                if (commentTextarea != undefined)
                {
//	Something's selected: wrap it
                var startPos = commentTextarea.selectionStart;
                        var endPos = commentTextarea.selectionEnd;
                        selectedText = commentTextarea.value.substring(startPos, endPos)

                        var textBeforeSelection = commentTextarea.value.substr(0, commentTextarea.selectionStart);
                        var textAfterSelection = commentTextarea.value.substr(commentTextarea.selectionEnd, commentTextarea.value.length);
                        commentTextarea.value = textBeforeSelection + start + selectedText + end + textAfterSelection;
                        }
        else
                {
//	Nothing selected, just inject at the end of the message
                commentTextarea.value += start;
                        commentTextarea.value += ' ';
                        commentTextarea.value += end;
                        }

        commentTextarea.focus();
                }

function replaceAll(find, replace, str)
        {
        return str.replace(new RegExp(find, 'g'), replace);
                }

function GetAchievementAndTooltipDiv(achID, achName, achDesc, achPoints, gameName, badgeName, inclSmallBadge, smallBadgeOnly)
        {
        var tooltipImageSize = 64;
                //achName = replaceAll( "\"", "\\&apos;", achName );
                //achDesc = replaceAll( "\"", "\\&apos;", achDesc );
                //gameName = replaceAll( "\"", "\\&apos;", gameName );

                var tooltip = "<div id=\'objtooltip\'>" +
                "<img src=\'http://i.retroachievements.org/Badge/" + badgeName + ".png\' width=" + tooltipImageSize + " height=" + tooltipImageSize + " />" +
                "<b>" + achName + " (" + achPoints.toString() + ")</b><br/>" +
                "<i>(" + gameName + ")</i><br/>" +
                "<br/>" +
                achDesc + "<br/>" +
                "</div>";
                tooltip = replaceAll('<', '&lt;', tooltip);
                tooltip = replaceAll('>', '&gt;', tooltip);
                tooltip = replaceAll("\'", "\\\'", tooltip);
                tooltip = replaceAll("\"", "&quot;", tooltip);
                var smallBadge = '';
                var displayable = achName + " (" + achPoints.toString() + ")";
                if (inclSmallBadge)
                {
                smallBadgePath = "http://i.retroachievements.org/Badge/" + badgeName + ".png";
                        smallBadge = "<img width='32' height='32' style='floatimg' src='" + smallBadgePath + "' alt=\"" + achName + "\" title=\"" + achName + "\" class='badgeimg' />";
                        if (smallBadgeOnly)
                        displayable = "";
                        }

        return "<div class='bb_inline' onmouseover=\"Tip('" + tooltip + "')\" onmouseout=\"UnTip()\" >" +
                "<a href='/Achievement/" + achID + "'>" +
                smallBadge +
                displayable +
                "</a>" +
                "</div>";
                }

function GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName, imageInstead)
        {
        var tooltipImageSize = 64;
                var consoleStr = "(" + consoleName + ")";
                var tooltip = "<div id=\'objtooltip\'>" +
                "<img src=\'" + gameIcon + "\' width=\'" + tooltipImageSize + "\' height=\'" + tooltipImageSize + "\' />" +
                "<b>" + gameTitle + "</b><br/>" +
                consoleStr +
                "</div>";
                tooltip = replaceAll('<', '&lt;', tooltip);
                tooltip = replaceAll('>', '&gt;', tooltip);
                tooltip = replaceAll("\'", "\\\'", tooltip);
                tooltip = replaceAll("\"", "&quot;", tooltip);
                var displayable = gameTitle + " " + consoleStr;
                if (imageInstead)
                displayable = "<img alt=\"started playing " + gameTitle + "\" title=\"Started playing " + gameTitle + "\" src='" + gameIcon + "' width='32' height='32' class='badgeimg' />";
                return "<div class='bb_inline' onmouseover=\"Tip('" + tooltip + "')\" onmouseout=\"UnTip()\" >" +
                "<a href='/Game/" + gameID.toString() + "'>" +
                displayable +
                "</a>" +
                "</div>";
                }

function GetUserAndTooltipDiv(user, points, motto, imageInstead, extraText)
        {
        var tooltipImageSize = 128;
                var tooltip = "<div id=\'objtooltip\'>";
                tooltip += "<table><tbody>";
                tooltip += "<tr>";
                //	Image
                tooltip += "<td class='fixedtooltipcolleft'><img src='/UserPic/" + user + ".png' width=\'" + tooltipImageSize + "\' height=\'" + tooltipImageSize + "\' /></td>";
                //	Username (points)
                tooltip += "<td class='fixedtooltipcolright'>";
                tooltip += "<b>" + user + "</b>";
                if (points !== null)
                tooltip += "&nbsp;(" + points.toString() + ")";
                //	Motto
                if (motto !== null && motto.length > 2)
                tooltip += "</br><span class='usermotto'>" + motto + "</span>";
                if (extraText.length > 0)
                tooltip += extraText;
                tooltip += "</td>";
                tooltip += "</tr>";
                tooltip += "</tbody></table>";
                tooltip += "</div>";
                //tooltip = escapeHtml( tooltip );

                tooltip = replaceAll('<', '&lt;', tooltip);
                tooltip = replaceAll('>', '&gt;', tooltip);
                tooltip = replaceAll("\'", "\\\'", tooltip); //&#039;
                tooltip = replaceAll("\"", "&quot;", tooltip);
                var displayable = user;
                if (imageInstead)
                displayable = "<img src='/UserPic/" + user + ".png' width='32' height='32' alt='" + user + "' title='" + user + "' class='badgeimg' />";
                return "<div class='bb_inline' onmouseover=\"Tip('" + tooltip + "')\" onmouseout=\"UnTip()\" >" +
                "<a href='/User/" + user + "'>" +
                displayable +
                "</a>" +
                "</div>";
                }

function GetLeaderboardAndTooltipDiv(lbID, lbName, lbDesc, gameName, gameIcon, displayable)
        {
        var tooltipImageSize = 64;
                var tooltip = "<div id=\'objtooltip\'>" +
                "<img src=\'" + gameIcon + "\' width=\'" + tooltipImageSize + "\' height=\'" + tooltipImageSize + "\' />" +
                "<b>" + lbName + "</b><br/>" +
                "<i>(" + gameName + ")</i><br/>" +
                "<br/>" +
                lbDesc + "<br/>" +
                "</div>";
                tooltip = replaceAll('<', '&lt;', tooltip);
                tooltip = replaceAll('>', '&gt;', tooltip);
                tooltip = replaceAll("\'", "\\\'", tooltip);
                tooltip = replaceAll("\"", "&quot;", tooltip);
                return "<div class='bb_inline' onmouseover=\"Tip('" + tooltip + "')\" onmouseout=\"UnTip()\" >" +
                "<a href=" + lbID + "'/leaderboardinfo.php?i='>" +
                displayable.toString() +
                "</a>" +
                "</div>";
                }

//	01:36 31/12/2013
function UpdateMailboxCount(messageCount)
        {
        $('body').find("#mailboxicon").attr("src", (messageCount > 0) ? 'http://i.retroachievements.org/Images/_MailUnread.png' : 'http://i.retroachievements.org/Images/_Mail.png');
                $('body').find("#mailboxcount").html(messageCount);
                }

$('#commentTextarea').on('keyup', function() {
// Store the maxlength and value of the field.
var maxlength = $(this).attr('maxlength');
        var val = $(this).val();
        var vallength = val.length;
        // Trim the field if it has content over the maxlength.
        if (vallength > maxlength) {
$(this).val(val.slice(0, maxlength));
        }
});
        function reloadTwitchContainer(videoID, vidWidth, vidHeight)
        {
        var vidHTML = "<object type='application/x-shockwave-flash' height='" + vidHeight + "' width='" + vidWidth + "' id='live_embed_player_flash' data='http://www.twitch.tv/widgets/live_embed_player.swf?channel=retroachievementsorg' bgcolor='#2A2A2A'><param name='allowFullScreen' value='true' /><param name='allowScriptAccess' value='always' /><param name='allowNetworking' value='all' /><param name='movie' value='http://www.twitch.tv/widgets/live_embed_player.swf' /><param name='flashvars' value='hostname=www.twitch.tv&channel=retroachievementsorg&auto_play=true&start_volume=25' /></object>";
                if (videoID !== 0 && archiveURLs[ videoID ] !== 0)
        {
        var vidTitle = archiveTitles[ videoID ];
                var vidURL = archiveURLs[ videoID ];
                var vidChapter = vidURL.substr(vidURL.lastIndexOf("/") + 1);
                vidHTML = "<object type='application/x-shockwave-flash' height='" + vidHeight + "' width='" + vidWidth + "' id='clip_embed_player_flash' data='http://www.twitch.tv/widgets/archive_embed_player.swf' bgcolor='#2A2A2A' ><param name='movie' value='http://www.twitch.tv/widgets/archive_embed_player.swf'><param name='allowScriptAccess' value='always'><param name='allowNetworking' value='all'><param name='allowFullScreen' value='true'><param name='flashvars' value='title=" + vidTitle + "&amp;channel=retroachievementsorg&amp;auto_play=true&amp;start_volume=25&amp;chapter_id=" + vidChapter + "'></object>";
        }

        $('.streamvid').html(vidHTML);
        }

jQuery(document).ready(function($) {
$('#commentTextarea').watermark('Enter a comment here...');
        $('.messageTextarea').watermark('Enter your message here...');
        $('.searchboxinput').watermark('Search the site...');
        $('.passwordresetusernameinput').watermark('Enter Username...');
        $('.searchboxgamecompareuser').watermark('Enter User...');
        $('#chatinput').watermark('Enter a comment here...');
        $('#chatinput:disabled').watermark('Please log in to join the chat!');
        $('.searchboxinput').autocomplete({source:'/requestsearch.php', minLength:2});
        $('.searchboxinput').autocomplete({select: function(event, ui) { return false; } });
        $('.searchboxinput').on("autocompleteselect", function(event, ui) {
window.location = ui.item.mylink;
        return false;
        });
        $('.searchboxgamecompareuser').autocomplete({source:'/requestsearch.php?p=gamecompare', minLength:2});
        $('.searchboxgamecompareuser').autocomplete({select: function(event, ui) { return false; } });
        $('.searchboxgamecompareuser').on("autocompleteselect", function(event, ui) {

var gameID = GetParameterByName("ID");
        if (window.location.pathname.substring(0, 6) == '/Game/')
        gameID = window.location.pathname.substring(6);
        window.location = '/gamecompare.php?ID=' + gameID + '&f=' + ui.item.id;
        return false;
        });
        $('.searchuser').autocomplete({source:'/requestsearch.php?p=user', minLength:2});
        $('.searchuser').autocomplete({select: function(event, ui) {

var TABKEY = 9;
        if (event.keyCode == TABKEY)
        {
        $('.searchusericon').attr('src', '/UserPic/' + ui.item.id + '.png');
                }
return false;
        }
});
        $('.searchuser').on("autocompleteselect", function(event, ui) {

$('.searchuser').val(ui.item.id);
        $('.searchusericon').attr('src', '/UserPic/' + ui.item.id + '.png');
        return false;
        });
// duplicated code?
        function repeat_fade($element, delay, duration) {
        $element.delay(delay).fadeToggle(duration, function () {
        run_animation($element, delay, duration);
        });
        };
        $('#devboxcontent').hide();
        $('#userinfoboxcontent').hide();
        $('.msgPayload').hide();
        $('#managevids').hide();
        $('#chatinput').width('75%');
        $('#commentTextarea').width('75%');
        $('#usermottoinput').watermark('Add your motto here! (No profanity please!)');
        });
        $(function () {
        function repeat_fade($element, delay, duration) {
        $element.delay(delay).fadeToggle(duration, function () {
        repeat_fade($element, delay, duration);
        });
        }
        repeat_fade($('.trophyimageincomplete'), 200, 300);
        });
        function removeComment(artID, commentID)
        {
        var posting = $.post("/dorequest.php", { r: "removecomment", a: artID, c: commentID });
                posting.done(onRemoveComment);
        }

function onRemoveComment(data)
        {
        var result = $.parseJSON(data)
                if (result.Success)
                {
                $('#artcomment_' + result.ArtID + '_' + result.CommentID).hide(400, function(event) {});
                        }
//alert( result.Success );
        }

function ResetTheme()
        {
//	Unload all themes...
        var allLinks = document.getElementsByTagName('link');
                var numLinks = allLinks.length;
                for (var i = 0; i < numLinks; i++)
                {
                var nextLink = allLinks[i];
                        if (nextLink.rel == "stylesheet")
                        {
                        if (nextLink.href.indexOf('css/rac') != - 1)
                                nextLink.disabled = true;
                                }
                }

//	Then load the one you selected:
        var cssToLoad = $('#themeselect :selected').val();
                var cssLink = $("<link rel='stylesheet' type='text/css' href='" + cssToLoad + "'>");
                $("head").append(cssLink);
                RA_SetCookie('RAPrefs_CSS', cssToLoad);
                }

function RA_SetCookie(cookiename, value)
        {
//	Finally persist as a cookie
        var days = 30;
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                var expires = "; expires=" + date.toGMTString();
                document.cookie = cookiename + "=" + value + expires;
                }

function refreshOnlinePlayers()
{
    var posting = $.post( "/requestcurrentlyonlinelist.php" );
    posting.done( onRefreshOnlinePlayers );

    $('#playersonlinebox').fadeTo( "fast", 0.0 );
    $('#playersonline-update').fadeTo( "fast", 0.0 );
}

function onRefreshOnlinePlayers( data )
{
    var playerList = $.parseJSON( data );
    var numPlayersOnline = playerList.length;

    var htmlOut = "<div>There are currently <strong>" + numPlayersOnline + "</strong> players online:</div>";

    for( var i = 0; i < numPlayersOnline; ++i )
    {
        var player = playerList[i];

        if( i > 0 && i == numPlayersOnline-1 )	//	last but one:
            htmlOut += " and ";
        else if( i > 0 )
            htmlOut += ", ";

        var extraText = "<br/>" + player.LastActivityAt + ": " + player.User + " " + player.LastActivity;
            htmlOut += GetUserAndTooltipDiv( player.User, player.RAPoints, player.Motto, false, extraText );
    }

    d = new Date();

    $('#playersonlinebox').html( htmlOut );
    $('#playersonlinebox').fadeTo( "fast", 1.0 );
    $('#playersonline-update').html( "Last updated at " + d.toLocaleTimeString() );
    $('#playersonline-update').fadeTo( "fast", 0.5 );
}

function refreshActivePlayers()
{
    var posting = $.post("/requestcurrentlyactiveplayers.php");
    posting.done(onRefreshActivePlayers);
    $('#activeplayersbox').fadeTo("fast", 0.0);
    $('#activeplayers-update').fadeTo("fast", 0.0);
}

function onRefreshActivePlayers(data)
{
    var playerList = JSON.parse(data)
    var numPlayersOnline = playerList.length;
    var htmlTitle = "<div>There are currently <strong>" + numPlayersOnline + "</strong> players online:</div>";
    $('#playersonlinebox').html(htmlTitle);
    $('#activeplayersbox').empty();
    var table = $('<table></table>').addClass('smalltable');
    var tbody = $('<tbody></tbody>');
    var headers = $('<tr></tr>');
    headers.append($('<th>></th>').text('User'));
    headers.append($('<th></th>').text('Game'));
    headers.append($('<th></th>').text('Currently...'));
    tbody.append(headers);
    table.append(tbody);
    
    for (var i = 0; i < numPlayersOnline; ++i)
    {
        var player = playerList[i];
        var userStamp = GetUserAndTooltipDiv(player.User, player.RAPoints, player.Motto, true, '');
        var userElement = $('<td></td>').append(userStamp);
        var gameElement;
        var activityElement;
        
        if (player.InGame)
        {
            gameElement = $('<td></td>').append(GetGameAndTooltipDiv(player.GameID, player.GameTitle, player.GameIcon, player.ConsoleName, true));
            activityElement = $('<td></td>').text(player.RichPresenceMsg);
        }
        else
        {
            gameElement = $('<td></td>').append("None");
            activityElement = $('<td></td>').append("Just Browsing");
        }

        var row = $('<tr></tr>').addClass('activeonlineplayer').append(userElement).append(gameElement).append(activityElement);
        tbody.append(row);
    }

    d = new Date();
    $('#activeplayersbox').append(table);
    $('#activeplayersbox').fadeTo("fast", 1.0);
    $('#activeplayers-update').html("Last updated at " + d.toLocaleTimeString());
    $('#activeplayers-update').fadeTo("fast", 0.5);
}
