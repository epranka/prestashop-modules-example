<div class="row">
    <div class="col-sm-6">
        {include file="./current_stats.tpl"}
    </div>
    <div class="col-sm-6">
        {include file="./last_stats.tpl"}
    </div>
</div>
<div class="row">
    {include file="./filtered_stats.tpl"}
</div>

<script>
    $(function() {
        var url_index = "/{$url_index}";
        {literal}
            function updateStats(e) {
                $(this).find('i').replaceWith('<i class="process-icon-loading"></i>');
                $(this).attr('disabled', 'disabled');
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: url_index + '&ajax=1',
                    data: {
                        action: 'updateStats'
                    }
                }).done(function(data) {
                    if(data && data.success) {
                        $('#last_stats').replaceWith(data.result);
                    }
                }).fail(function(jqXHR, textStatus) {
                    console.log(jqXHR);
                    console.log(textStatus);
                });
            }
            $('body').undelegate('button[name=updateStats]', 'click', updateStats);
            $('body').delegate('button[name=updateStats]', 'click', updateStats);
            
            function filterStats(e) {
                e.preventDefault();
                var values = {};
                $.each($(this).serializeArray(), function(i, field) {
                    values[field.name] = field.value;
                });
                $(this).find('button[name=filterStats] i').replaceWith('<i class="process-icon-loading"></i>');
                $(this).find('button[name=filterStats]').attr('disabled', 'disabled');
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: url_index + '&ajax=1',
                    data: {
                        action: 'filterStats',
                        date_from: $(this).find('input[name=date_from]').val(),
                        date_to: $(this).find('input[name=date_to]').val()
                    }
                }).done(function(data) {
                    if(data && data.success) {
                        $('#filtered_stats').replaceWith(data.result);
                    }
                }).fail(function(jqXHR, textStatus) {
                    console.log(jqXHR);
                    console.log(textStatus);
                });
            }
            $('body').undelegate('#filtered_stats form', 'submit', filterStats);
            $('body').delegate('#filtered_stats form', 'submit', filterStats);

            function clearStats(e) {
                e.preventDefault();
                $(this).find('i').replaceWith('<i class="process-icon-loading"></i>');
                $(this).attr('disabled', 'disabled');
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: url_index + '&ajax=1',
                    data: {
                        action: 'clearStats',
                    }
                }).done(function(data) {
                    if(data && data.success) {
                        $('#filtered_stats').replaceWith(data.result);
                    }
                }).fail(function(jqXHR, textStatus) {
                    console.log(jqXHR);
                    console.log(textStatus);
                });
            }

            $('body').undelegate('#filtered_stats form button[name=clearStats]', 'click', clearStats);
            $('body').delegate('#filtered_stats form button[name=clearStats]', 'click', clearStats);
        {/literal}
    });
</script>