<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CLI_Adventure_State_Manager {

  /**
   * Initialize or load an existing state.
   *
   * @param array $state  Raw state from the AJAX call.
   * @return array        State with defaults filled in.
   */
  public function load( $state ) {
      // On first run, start at the intro scene
      if ( empty( $state['scene'] ) ) {
          $state['scene']     = 'intro.json';
          $state['inventory'] = [];
          $state['flags']     = [];
      }
      return $state;
  }

  /**
   * “Save” state (we’re just passing it back for now).
   *
   * @param array $state  Updated state.
   * @return array        State to carry forward.
   */
  public function save( $state ) {
      // Later we could persist to session or DB here
      return $state;
  }
}
