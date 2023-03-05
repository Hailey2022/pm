function convertCurrency(r) { var t = ["零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖"], e = ["", "拾", "佰", "仟"], n = ["", "万", "亿", "兆"], s = ["角", "分", "毫", "厘"], a = "整", f = "元", i = 1e15, u, o, v = "", l; if ("" == r) return ""; if ((r = parseFloat(r)) >= 1e15) return ""; if (0 == r) return v = "零元整"; if (-1 == (r = r.toString()).indexOf(".") ? (u = r, o = "") : (u = (l = r.split("."))[0], o = l[1].substr(0, 4)), parseInt(u, 10) > 0) { for (var p = 0, b = u.length, c = 0; c < b; c++) { var g, h = b - c - 1, I = h / 4, d = h % 4; "0" == (g = u.substr(c, 1)) ? p++ : (p > 0 && (v += "零"), p = 0, v += t[parseInt(g)] + e[d]), 0 == d && p < 4 && (v += n[I]) } v += "元" } if ("" != o) for (var x = o.length, c = 0; c < x; c++) { var g; "0" != (g = o.substr(c, 1)) && (v += t[+g] + s[c]) } return "" == v ? v += "零元整" : "" == o && (v += "整"), v }

function formatNum(num) {
    return Math.round(num * Math.pow(10, 6)) / Math.pow(10, 6);
}
function handleDelete(id_str) {
    if (!confirm('注意！是否确认删除附件？\n《' + $(id_str).find("input:not(:hidden)").val() + '》')) {
        return false;
    }
    $(id_str).remove();

}
// TODO: rename it to 上传 when there isn't 
function renameButton() {
    $('div.xf_input li:has(input:hidden)').find('xfupload').text('增加');
}
// function upload() {
//     // TODO: 校对概算价(小数)
//     if ($("#input-approximatePrice").val() != (parseInteger($("#input-fee1").val()) + parseInteger($("#input-fee2").val()) + parseInteger($("#input-fee3").val()) + parseInteger($("#input-fee4").val()) + parseInteger($("#input-fee5").val()))) {
//         alert("请检查批复概算价的值")
//     } else {
//         $('#project_submit').click()
//     }
// }

function to10k(that) {
    num = that.val()
    if (isNaN(num)) {
        that.next().next().val("请输入正确的数字")
    } else {
        that.next().next().val(formatNum(num / 10000) + "万元")
    }
}

$(function () {
    $('.to10k').each(function () {
        to10k($(this))
    })
    $("xfupload").on("click", function () {
        var title = $(this).prev('span').text().replace("：", "") + "上传"
        var id = $(this).parent().attr('id')
        // var attr = $(this).attr('date')
        var key = $(this).attr('file_id')
        var tpl = "tpl";
        // if (typeof attr !== 'undefined' && attr !== false) {
        //     var tpl = "tpl_with_date";
        // } else {
        //     var tpl = "tpl";
        // }
        openUploadDialog(title, function (dialog, files) {
            var self_tpl = $('#' + tpl).html();
            var html = '';
            $.each(files, function (i, item) {
                var itemtpl = self_tpl;
                itemtpl = itemtpl.replace(/\{id\}/g, item.id);
                itemtpl = itemtpl.replace(/\{url\}/g, item.url);
                itemtpl = itemtpl.replace(/\{preview_url\}/g, item.preview_url);
                itemtpl = itemtpl.replace(/\{filepath\}/g, item.filepath);
                itemtpl = itemtpl.replace(/\{name\}/g, item.name);
                itemtpl = itemtpl.replace(/\{key\}/g, key);
                html += itemtpl;
            });
            $('#' + id).append(html);
            files.length && renameButton();
        }, null, 1, 'file');
    });
    //TODO: sometimes onkeyup not working!!!!!!!!!!!!!!
    $('.to10k').on('change keyup', function () {
        to10k($(this))
    });

    renameButton();
})