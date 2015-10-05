/* global moment */

$(function () {

    function update() {
        $.post('/monitor/getdata/', $('#filter').serialize(),
            function (data) {
                $('#monitor > tbody').html(data);
            });
    };
    
    function setDefaults() {
        
        // default values to filter
        
        var dt = moment().format('YYYY-MM-DD');
        $('input[name="bdate"], input[name="edate"]').val(dt);
        $('input[name="btime"]').val('00:00');
        $('input[name="etime"]').val('23:59');
        
        $('.selectpicker').selectpicker('deselectAll');
        update();
    }

    var tmr = undefined;
    $('input[type="text"], select').on('keyup change', function () {
        clearTimeout(tmr);
        tmr = setTimeout(function () {
            update();
        }, 1500);
    });
    
    $('#update').click(function () {
        update();
        return false;
    });
    
    $('#reset').click(function () {
        $('#filter').trigger('reset');
        setDefaults();
        return false;
    });

    setDefaults();
    //update();
});

