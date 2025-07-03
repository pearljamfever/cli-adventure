jQuery(function($) {
  var $container = $('#cli-adventure-terminal');

  var term = $container.terminal(function(command, termObj) {
    var cmd = command.trim().toLowerCase();

    // Client-side 'map' command
    if (cmd === 'map') {
      var $map = $('<div>').css({
        display: 'grid',
        gridTemplateColumns: 'repeat(3, 1fr)',
        gap: '4px',
        textAlign: 'center',
        margin: '1em 0'
      });
      var layout = [
        '', 'Attic', '',
        'Servants', 'Master Suite', 'Guest BR 1',
        'Kitchen', 'Hallway', 'Guest BR 2',
        'Dining Room', 'Foyer', 'Guest BR 3',
        'Ballroom', 'Gallery', 'Guest BR 4',
        'Library', 'Basement', '???',
      ];
      layout.forEach(function(label) {
        var $cell = $('<div>').css({
          border: '1px solid currentColor',
          padding: '4px',
          minHeight: '24px',
          background: '#000'
        }).text(label);
        $map.append($cell);
      });
      termObj.echo($map);
      termObj.resume();
      termObj.focus();
      return;
    }

    // Default AJAX handling for game commands
    termObj.pause();
    $.post(CLI_ADVENTURE_Ajax.ajax_url, {
      action:  'cli_adventure',
      nonce:   CLI_ADVENTURE_Ajax.nonce,
      command: command,
      state:   JSON.stringify(termObj.state || {})
    })
    .done(function(response) {
      if (response.success) {
        response.data.output.forEach(function(line) {
          termObj.echo(line);
        });
        termObj.state = response.data.state;
        $container.scrollTop($container.prop('scrollHeight'));
      }
    })
    .always(function() {
      termObj.resume();
      termObj.focus();
    });

  }, {
    greetings: '',            // remove default greeting
    name:      'cli_adventure',
    height:    '100%',
    prompt:    '> ',
    onBlur:    function() { this.focus(); }
  }).focus();

  // Manually echo greeting so it uses default styling
  term.echo('Welcome to CLI Adventure! Type "help" to begin.');
});
