function showTooltip(x, y, contents) {
    $('<div id="tooltip">' + contents + '</div>').css( {
        position: 'absolute',
        top: y + 5,
        left: x + 5,
        border: '1px solid #fdd',
        padding: '2px',
        'background-color': '#fee',
        opacity: 0.80
    }).appendTo("body"); //.fadeIn(200);
}
 // helper for returning the weekends in a period
function weekendAreas(axes) {
    var markings = [];
    var d = new Date(axes.xaxis.min);
    // go to the first Saturday
    d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
    d.setUTCSeconds(0);
    d.setUTCMinutes(0);
    d.setUTCHours(0);
    var i = d.getTime();
    do {
        // when we don't set yaxis the rectangle automatically
        // extends to infinity upwards and downwards
        markings.push({ xaxis: { from: i, to: i + 2 * 24 * 60 * 60 * 1000 } });
        i += 7 * 24 * 60 * 60 * 1000;
    } while (i < axes.xaxis.max);

    return markings;
}

var previousPoint = false, itemdata = false;

$(function(){
    $('.navbar-inner li.dropdown').mouseover(function(){
        $('.dropdown-menu').hide();
        $(this).find('.dropdown-menu').show();
    });

    $('.navbar-inner li.dropdown').mouseout(function(){
        $('.dropdown-menu').hide();
        return false;
    });

    $('.syntax a.dropdown-toggle').click(function(){
        $(this).closest('div.syntax').find('.dropdown-menu').toggle();
        return false;
    });
    
    $('.thumbs').live("click",function(){
        $('.form_field #email').val('');
        $('.form_field #namesearch').val('');
    });
});