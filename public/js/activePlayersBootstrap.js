function CreateCardIconDiv(type, id, title, icon, url) {
  return '<div class=\'inline\' '
    + 'ontouchstart="mobileSafeTipEvents.touchStart()" onmouseover="mobileSafeTipEvents.mouseOver(loadCard(this, \'' + type + '\', \'' + id + '\'))" onmouseout="UnTip()" >'
    + '<a href=\'' + url + '\'>'
    + '<img src=\'' + mediaAsset(icon) + '\' width=\'32\' height=\'32\' '
    + ' alt="' + title + '" title="' + title + '" class=\'badgeimg\' loading=\'lazy\' />'
    + '</a>'
    + '</div>';
}

function CacheCardDiv(type, id, title, subtitle, icon) {
  $html = '<div class=\'tooltip-body flex items-start\' style=\'max-width: 400px\'>'
    + '<img style=\'margin-right:5px\' src=\'' + mediaAsset(icon) + '\' width=64 height=64 \/>'
    + '<div><b>' + title + '</b><br>' + subtitle + '</div></div>';
  useCard(type, id, null, $html);
}

var ActivePlayersViewModel = function () {
  // eslint-disable-next-line @typescript-eslint/no-this-alias
  var self = this;
  this.players = ko.observableArray([]);
  this.hasError = ko.observable(false);
  this.lastUpdate = ko.observable(new Date());
  this.shouldMenuBeVisible = ko.observable(false);
  this.playerFilterText = ko.observable(localStorage.getItem('filterString') || '')
    .extend({
      rateLimit: {
        timeout: 300,
        method: 'notifyWhenChangesStop'
      }
    });
  this.isLoading = ko.observable(false);
  this.rememberFiltersValue = ko.observable(localStorage.getItem('rememberFilters'));

  this.rememberFiltersValue.subscribe(function () {
    self.UpdateFilterStorage();
  });

  this.shouldRememberFilters = ko.pureComputed(function () {
    return !!this.rememberFiltersValue();
  }, this);

  this.playerFilterText.subscribe(function () {
    self.UpdateFilterStorage();
  });

  this.numberOfPlayersActive = ko.pureComputed(function () {
    return this.players().length;
  }, this);

  this.numberOfFilteredPlayers = ko.pureComputed(function () {
    return this.filteredPlayers().length;
  }, this);

  this.lastUpdateRender = ko.pureComputed(function () {
    return 'Last updated at ' + this.lastUpdate().toLocaleTimeString();
  }, this);

  this.usersAreFiltered = ko.pureComputed(function () {
    return this.numberOfFilteredPlayers() < this.players().length;
  }, this);

  this.filteredPlayers = ko.pureComputed(function () {
    return _.filter(this.players(), (player) => {
      var lowercaseFilterTextTerms = this.playerFilterText().toLowerCase().split('|');
      var matchFound = false;
      lowercaseFilterTextTerms.forEach((lowercaseFilterText) => {
        matchFound = lowercaseFilterText !== ''
          && (player.username().toLowerCase().includes(lowercaseFilterText)
            || player.gameTitle().toLowerCase().includes(lowercaseFilterText)
            || player.consoleName().toLowerCase().includes(lowercaseFilterText)
            || player.richPresence().toLowerCase().includes(lowercaseFilterText));
      });
      return this.playerFilterText() === '' || matchFound;
    });
  }, this);

  this.UpdateFilterStorage = function () {
    if (this.shouldRememberFilters()) {
      localStorage.setItem('rememberFilters', this.rememberFiltersValue());
      localStorage.setItem('filterString', this.playerFilterText());
    } else {
      localStorage.removeItem('rememberFilters');
      localStorage.removeItem('filterString');
    }
  };

  this.OnActivePlayersMenuButtonClick = function () {
    this.shouldMenuBeVisible(!this.shouldMenuBeVisible());
  };

  this.ConvertToObservablePlayer = function (player) {
    CacheCardDiv('game', player.GameID, player.GameTitle, player.ConsoleName, player.GameIcon);
    return {
      username: ko.observable(player.User),
      points: ko.observable(player.RAPoints),
      gameTitle: ko.observable(player.GameTitle),
      consoleName: ko.observable(player.ConsoleName),
      richPresence: ko.observable(player.RichPresenceMsg),
      playerHtml: ko.observable(CreateCardIconDiv('user', player.User, player.User, '/UserPic/' + player.User + '.png', '/user/' + player.User)),
      gameHtml: ko.observable(CreateCardIconDiv('game', player.GameID, player.GameTitle, player.GameIcon, '/game/' + player.GameID))
    };
  };

  this.RefreshActivePlayers = function () {
    self.isLoading(true);
    $.post('/request/user/list-currently-active.php')
      .done(function (data) {
        self.players(data.reduce(function (result, player) {
          return result.concat(self.ConvertToObservablePlayer(player));
        }, []));

        self.lastUpdate(new Date());
        self.hasError(false);
        self.isLoading(false);
      })
      .fail(function () {
        self.hasError(true);
        self.isLoading(false);
      });
  };

  this.init = function () {
    const FIVE_MINUTES = 5000 * 60;

    this.RefreshActivePlayers();
    setInterval(this.RefreshActivePlayers, FIVE_MINUTES);
  };

  // This check will fail in Safari.
  if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
      this.init();
    });
  } else {
    this.init();
  }
};

ko.applyBindings(new ActivePlayersViewModel(), document.getElementById('active-players-component'));
