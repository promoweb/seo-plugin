jQuery(document).ready(function($) {
    // Handle AJAX actions
    $(document).on('click', '.action-button', function() {
        var action = $(this).data('action');
        var button = $(this);
        
        button.prop('disabled', true).text('Processing...');
        
        $.post(seoDashboard.ajaxurl, {
            action: action,
            _wpnonce: seoDashboard.nonce
        }, function(response) {
            if (response.success) {
                button.text('Completed!').removeClass('button-primary').addClass('button-success');
            } else {
                button.text('Error!').removeClass('button-primary').addClass('button-error');
            }
        });
    });
    
    // Initialize charts
    if ($('#competitor-chart').length) {
        // ChartJS initialization would go here
    }
});