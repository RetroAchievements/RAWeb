var ActivePlayersViewModel = function() {
    var self = this;
    this.players = ko.observableArray([]);
    this.hasError = ko.observable(false);
    this.lastUpdate = ko.observable(new Date());
    this.shouldMenuBeVisible = ko.observable(false);
    this.playerFilterText = ko.observable(localStorage.getItem('filterString') || '').extend({ rateLimit: { timeout: 300, method: 'notifyWhenChangesStop' }});
    this.isLoading = ko.observable(false);
    this.rememberFiltersValue = ko.observable(localStorage.getItem('rememberFilters'));

    this.rememberFiltersValue.subscribe(function() {
        self.UpdateFilterStorage();
    });

    this.shouldRememberFilters = ko.pureComputed(function() {
        return !!this.rememberFiltersValue();
    }, this);

    this.playerFilterText.subscribe(function() {
        self.UpdateFilterStorage();
    });

    this.numberOfPlayersActive = ko.pureComputed(function() {
        return this.players().length;
    }, this);

    this.numberOfFilteredPlayers = ko.pureComputed(function() {
        return this.filteredPlayers().length;
    }, this);

    this.lastUpdateRender = ko.pureComputed(function() {
        return 'Last updated at ' + this.lastUpdate().toLocaleTimeString();
    }, this);

    this.usersAreFiltered = ko.pureComputed(function() {
        return this.numberOfFilteredPlayers() < this.players().length;
    }, this);

    this.filteredPlayers = ko.pureComputed(function() {
        return _.filter(this.players(), player => {
            var lowercaseFilterTextTerms = this.playerFilterText().toLowerCase().split('|');
            var matchFound = false;
            lowercaseFilterTextTerms.forEach(lowercaseFilterText => {
                matchFound ||= lowercaseFilterText !== '' &&
                (player.username().toLowerCase().includes(lowercaseFilterText)
                || player.gameTitle().toLowerCase().includes(lowercaseFilterText)
                || player.consoleName().toLowerCase().includes(lowercaseFilterText)
                || player.richPresence().toLowerCase().includes(lowercaseFilterText));
            })
            return this.playerFilterText() === '' || matchFound;
        });
    }, this);

    this.UpdateFilterStorage = function() {
        if (this.shouldRememberFilters()) {
            localStorage.setItem('rememberFilters', this.rememberFiltersValue());
            localStorage.setItem('filterString', this.playerFilterText());
        } else {
            localStorage.removeItem('rememberFilters');
            localStorage.removeItem('filterString');
        }
    };

    this.OnActivePlayersMenuButtonClick = function() {
        this.shouldMenuBeVisible(!this.shouldMenuBeVisible());
    };

    this.ConvertToObservablePlayer = function(player) {
        return {
            username: ko.observable(player.User),
            points: ko.observable(player.RAPoints),
            gameTitle: ko.observable(player.GameTitle),
            consoleName: ko.observable(player.ConsoleName),
            richPresence: ko.observable(player.RichPresenceMsg),
            playerHtml: ko.observable(GetUserAndTooltipDiv(player.User, player.RAPoints, player.Motto, true, '')),
            gameHtml: ko.observable(GetGameAndTooltipDiv(player.GameID, player.GameTitle, player.GameIcon, player.ConsoleName, true))
        };
    };

    this.RefreshActivePlayers = function() {
        self.isLoading(true);
        $.ajax({
            url: '/request/user/list-currently-active.php',
            method: 'GET',
            success: function(data) {
                self.players([]);
                data.forEach(player => {
                    self.players.push(self.ConvertToObservablePlayer(player));
                });

                self.lastUpdate(new Date());
                self.hasError(false);
            },
            error: function() {
                self.hasError(true);
            },
            complete: function() {
                self.isLoading(false);
            }
        });
    };

    this.RefreshActivePlayers();
    setInterval(this.RefreshActivePlayers, 5000 * 60);
};

ko.applyBindings(new ActivePlayersViewModel(), document.getElementById('active-players-component'));
