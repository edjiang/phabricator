<?php

final class PHUICalendarListView extends AphrontTagView {

  private $events = array();
  private $blankState;
  private $view;

  private function getView() {
    return $this->view;
  }

  public function setView($view) {
    $this->view = $view;
    return $this;
  }

  public function addEvent(AphrontCalendarEventView $event) {
    $this->events[] = $event;
    return $this;
  }

  public function showBlankState($state) {
    $this->blankState = $state;
    return $this;
  }

  protected function getTagName() {
    return 'div';
  }

  protected function getTagAttributes() {
    require_celerity_resource('phui-calendar-css');
    require_celerity_resource('phui-calendar-list-css');

    return array(
      'sigil' => 'calendar-event-list',
      'class' => 'phui-calendar-event-list',
    );
  }

  protected function getTagContent() {
    if (!$this->blankState && empty($this->events)) {
      return '';
    }

    $singletons = array();
    $allday = false;
    foreach ($this->events as $event) {
      $start_epoch = $event->getEpochStart();

      if ($event->getIsAllDay()) {
        $timelabel = pht('All Day');
      } else {
        $timelabel = phabricator_time(
          $event->getEpochStart(),
          $this->getUser());
      }

      $icon_icon = $event->getIcon();
      $icon_color = $event->getIconColor();

      $dot = id(new PHUIIconView())
        ->setIcon($icon_icon, $icon_color)
        ->addClass('phui-calendar-list-item-icon');

      $title = phutil_tag(
        'span',
        array(
          'class' => 'phui-calendar-list-title',
        ),
        $this->getEventTitle($event, $allday));
      $time = phutil_tag(
        'span',
        array(
          'class' => 'phui-calendar-list-time',
        ),
        $timelabel);

      $event_classes = array();
      $event_classes[] = 'phui-calendar-list-item';
      if ($event->getIsAllDay()) {
        $event_classes[] = 'all-day';
      }

      if ($event->getIsCancelled()) {
        $event_classes[] = 'event-cancelled';
      }

      $tip = $this->getEventTooltip($event);
      if ($this->getView() == 'day') {
        $tip_align = 'E';
      } else if ($this->getView() == 'month') {
        $tip_align = 'N';
      } else {
        $tip_align = 'W';
      }

      $content = javelin_tag(
        'a',
        array(
          'href' => $event->getURI(),
          'sigil' => 'has-tooltip',
          'meta'  => array(
            'tip'  => $tip,
            'size' => 200,
            'align' => $tip_align,
          ),
        ),
        array(
          $dot,
          $time,
          $title,
        ));

      $singletons[] = phutil_tag(
        'li',
        array(
          'class' => implode(' ', $event_classes),
        ),
        $content);
    }

    if (empty($singletons)) {
      $singletons[] = phutil_tag(
        'li',
        array(
          'class' => 'phui-calendar-list-item-empty',
        ),
        pht('Clear sailing ahead.'));
    }

    $list = phutil_tag(
      'ul',
      array(
        'class' => 'phui-calendar-list',
      ),
      $singletons);

    return $list;
  }

  private function getEventTitle($event) {
    $class = 'phui-calendar-item';
    return phutil_tag(
      'span',
      array(
        'class' => $class,
      ),
      $event->getName());
  }

  private function getEventTooltip(AphrontCalendarEventView $event) {
    $viewer = $this->getViewer();
    $time_key = PhabricatorTimeFormatSetting::SETTINGKEY;
    $time_pref = $viewer->getUserSetting($time_key);

    Javelin::initBehavior('phabricator-tooltips');

    $start = id(AphrontFormDateControlValue::newFromEpoch(
      $viewer,
      $event->getEpochStart()));

    $end = id(AphrontFormDateControlValue::newFromEpoch(
      $viewer,
      $event->getEpochEnd()));

    $start_date = $start->getDateTime()->format('m d Y');
    $end_date = $end->getDateTime()->format('m d Y');

    if ($event->getIsAllDay()) {
      if ($start_date == $end_date) {
        $tip = pht('All day');
      } else {
        $tip = pht(
          'All day, %s - %s',
          $start->getValueAsFormat('M j, Y'),
          $end->getValueAsFormat('M j, Y'));
      }
    } else {
      if ($start->getValueDate() == $end->getValueDate()) {
        $tip = pht(
          '%s - %s',
          $start->getValueAsFormat($time_pref),
          $end->getValueAsFormat($time_pref));
      } else {
        $tip = pht(
          '%s - %s',
          $start->getValueAsFormat('M j, Y, '.$time_pref),
          $end->getValueAsFormat('M j, Y, '.$time_pref));
      }
    }
    return $tip;
  }

  public function getIsViewerInvitedOnList() {
    foreach ($this->events as $event) {
      if ($event->getViewerIsInvited()) {
        return true;
      }
    }
    return false;
  }
}
