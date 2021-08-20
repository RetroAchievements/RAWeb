var ActivePlayersViewModel = function() {
    var self = this;
    this.numberOfPlayersActive = ko.pureComputed(function() {
        return this.players().length;
    }, this);
    this.players = ko.observableArray([]);
    this.hasError = ko.observable(false);
    this.lastUpdate = ko.observable(new Date());
    this.lastUpdateRender = ko.pureComputed(function() {
        return 'Last updated at ' + this.lastUpdate().toLocaleTimeString();
    }, this);

    this.ConvertToObservablePlayer = function(player) {
        return {
            playerHtml: ko.observable(GetUserAndTooltipDiv(player.User, player.RAPoints, player.Motto, true, '')),
            gameHtml: ko.observable(GetGameAndTooltipDiv(player.GameID, player.GameTitle, player.GameIcon, player.ConsoleName, true)),
            richPresence: ko.observable(player.RichPresenceMsg),
            isGameCurrentUserHasDevved: ko.observable(player.IsGameCurrentUserHasDevved)
        };
    };

    this.RefreshActivePlayers = function() {
        $.ajax({
            url: '/request/user/list-currently-active.php',
            method: 'GET',
            success: function(data) {
                self.players([]);
                data.forEach(player => {
                    self.players.push(self.ConvertToObservablePlayer(player));
                    //console.log(player);
                });

                self.lastUpdate(new Date());
                self.hasError(false);
            },
            error: function() {
                self.hasError(true);
            },
            complete: function() {
            }
        });
    };

    this.RefreshActivePlayers();
    setInterval(this.RefreshActivePlayers, 5000 * 60);
};

ko.applyBindings(new ActivePlayersViewModel(), document.getElementById('active-players-component'));
