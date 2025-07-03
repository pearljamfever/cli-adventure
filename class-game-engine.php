<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once CLI_ADVENTURE_PATH . 'includes/class-state-manager.php';

class CLI_Adventure_Game_Engine {
    /**
     * Handle a user command, transition scenes, and build output.
     * Supports optional ASCII art in scene JSON, shown on 'look' or on enter.
     * Adds a special 'map' command to view the mansion map.
     *
     * @param array  $state    Current game state.
     * @param string $command  Raw user input.
     * @return array           ['output' => array, 'state' => array]
     */
    public function handle_command( $state, $command ) {
        $stateManager = new CLI_Adventure_State_Manager();
        $state        = $stateManager->load( $state );

        // Load current scene JSON
        $scene_file = CLI_ADVENTURE_PATH . 'includes/story/' . $state['scene'];
        if ( ! file_exists( $scene_file ) ) {
            return [
                'output' => [ "Error: scene '{$state['scene']}' not found." ],
                'state'  => $state,
            ];
        }
        $scene = json_decode( file_get_contents( $scene_file ), true );

        $output = [];
        $cmd    = strtolower( trim( $command ) );

        // ——— Safe Keypad Puzzle ———
        // 1) If we’re awaiting the 4-digit code, handle it
        if ( ! empty( $state['awaiting_code'] ) ) {
            if ( preg_match( '/^\d{4}$/', $cmd ) ) {
                if ( $cmd === '1995' ) {
                    $output[]             = 'Beep—correct code! The safe clicks open. That was too easy.';
                    $state['safe_opened'] = true;
                } else {
                    $output[] = 'Beep—incorrect code.';
                }
                unset( $state['awaiting_code'] );
            } else {
                $output[] = 'Please enter exactly four digits.';
            }
            return [ 'output' => $output, 'state' => $state ];
        }

        // 2) If in the safe scene and user types "enter code", switch into keypad mode
        if ( isset( $state['scene'] ) 
            && $state['scene'] === 'safe.json' 
            && strtolower( $cmd ) === 'enter code' 
        ) {
            $output[]               = 'Enter 4-digit code:';
            $state['awaiting_code'] = true;
            return [ 'output' => $output, 'state' => $state ];
        }
        // ————————— end Safe Keypad Puzzle —————————


        // Special 'map' command: display full mansion map ASCII
        if ( 'map' === $cmd ) {
            $map_path = CLI_ADVENTURE_PATH . 'includes/art/map.txt';
            if ( file_exists( $map_path ) ) {
                $lines = file( $map_path, FILE_IGNORE_NEW_LINES );
                foreach ( $lines as $line ) {
                    $output[] = $line;
                }
            } else {
                $output[] = 'Map not available.';
            }
            return [ 'output' => $output, 'state' => $state ];
        }

        // Core commands
        if ( 'help' === $cmd ) {
            $output[] = 'Available commands:';
            foreach ( array_keys( $scene['commands'] ) as $c ) {
                $output[] = "  • {$c}";
            }
            $output[] = '  • map';

        } elseif ( 'look' === $cmd ) {
            if ( ! empty( $scene['ascii_art'] ) ) {
                $art_path = CLI_ADVENTURE_PATH . 'includes/art/' . $scene['ascii_art'];
                if ( file_exists( $art_path ) ) {
                    $lines = file( $art_path, FILE_IGNORE_NEW_LINES );
                    foreach ( $lines as $line ) {
                        $output[] = $line;
                    }
                }
            } else {
                $output[] = $scene['description'];
            }

        } elseif ( isset( $scene['commands'][ $cmd ] ) ) {
            $target = $scene['commands'][ $cmd ];

            if ( substr( $target, -5 ) === '.json' ) {
                // Scene transition
                $state['scene'] = $target;
                $next_file      = CLI_ADVENTURE_PATH . 'includes/story/' . $target;
                $next           = json_decode( file_get_contents( $next_file ), true );

                // Optionally show ASCII art on enter
                $show_on_enter = isset( $next['show_on_enter'] ) ? $next['show_on_enter'] : true;
                if ( $show_on_enter && ! empty( $next['ascii_art'] ) ) {
                    $art_path = CLI_ADVENTURE_PATH . 'includes/art/' . $next['ascii_art'];
                    if ( file_exists( $art_path ) ) {
                        $lines = file( $art_path, FILE_IGNORE_NEW_LINES );
                        foreach ( $lines as $line ) {
                            $output[] = $line;
                        }
                    }
                }

                // Scene description
                $output[] = $next['description'];

                // Automatically list available commands
                $output[] = 'Available commands:';
                foreach ( array_keys( $next['commands'] ) as $c ) {
                    $output[] = "  • {$c}";
                }
                $output[] = '  • map';

            } else {
                // Inline message or special command
                $output[] = is_string( $target ) ? $target : print_r( $target, true );
            }

        } else {
            // Unknown command
            $output[] = "I don't understand '{$command}'. Type 'help' for commands.";
        }

        // Persist updated state
        $state = $stateManager->save( $state );

        return [
            'output' => $output,
            'state'  => $state,
        ];
    }
}
