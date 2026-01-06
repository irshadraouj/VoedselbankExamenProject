//fetch segments
var segments = location.href.replace('https://', '').replace('http://', '').replace('www', '').split('/');
var last_segment = segments[segments.length-1];
var second_last_segment = segments[segments.length-2];
var closedStyle = 'right: 4px;top: 8px;position: absolute;font-size: 10px;';

//license menu on the module page
if((second_last_segment === 'wyvern' || last_segment === 'wyvern') && typeof EE.wyvern_license_entered !== 'undefined' && !EE.wyvern_license_entered) {
    $('.box.sidebar h2:last-child').append('<span class="st-closed" style="'+closedStyle+'">Unlicensed</span>');
} else if((second_last_segment === 'wyvern' || last_segment === 'wyvern') && typeof EE.wyvern_license_valid !== 'undefined' && !EE.wyvern_license_valid) {
    $('.box.sidebar h2:last-child').append('<span class="st-closed" style="'+closedStyle+'">Invalid license</span>');
} else if((second_last_segment === 'wyvern' || last_segment === 'wyvern') && EE.wyvern_license_valid) {
    $('.box.sidebar h2:last-child').append('<span class="st-info" style="'+closedStyle+'">Valid license</span>');
}

//addon overview
if(last_segment === 'addons' && typeof EE.wyvern_license_entered !== 'undefined' && !EE.wyvern_license_entered) {
    $('.tbl-wrap a:contains("Wyvern")').parents('tr').find('.toolbar').append('<li class="txt-only"><a class="no">Unlicensed</a></li>');
} else if(last_segment === 'addons' && typeof EE.wyvern_license_valid !== 'undefined' && !EE.wyvern_license_valid) {
    $('.tbl-wrap a:contains("Wyvern")').parents('tr').find('.toolbar').append('<li class="txt-only"><a class="no">Invalid license</a></li>');
}

//license page
if(second_last_segment === 'wyvern' && typeof EE.wyvern_license_entered !== 'undefined' && !EE.wyvern_license_entered) {
    $('#wyvern_license_status .unlicensed').show();
} else if(second_last_segment === 'wyvern' && typeof EE.wyvern_license_valid !== 'undefined' && !EE.wyvern_license_valid) {
    $('#wyvern_license_status .invalid_license').show();
} else {
    $('#wyvern_license_status .valid_license').show();
}

