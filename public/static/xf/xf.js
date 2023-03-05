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
$(function () {
    $("xfupload").on("click", function () {
        var title = $(this).prev('span').text().replace("：", "") + "上传"
        var id = $(this).parent().attr('id')
        var attr = $(this).attr('date')
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
    //TODO: onkeyup not working!!!!!!!!!!!!!!
    $('.to10k').on('change keyup', function () {
        num = $(this).val()
        if (isNaN(num)) {
            $(this).next().next().val("请输入正确的数字")
        } else {
            $(this).next().next().val(formatNum(num / 10000) + "万元")
        }
    });

    renameButton();
})