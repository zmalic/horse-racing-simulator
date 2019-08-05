Vue.use(VueMaterial.default, axios);
const url = 'app.php';
app = new Vue({
    el: '#app',
    data: () => ({
        errors: [],
        hasErrors: false,
        showSnackbar: true,
        races: [],
        finishedRaces: [],
        bestTimeEver: {}
    }),
    mounted:function(){
        this.getStats()
    },
    created:function(){
        setInterval(() => this.getStats(), 5000);
    },
    methods: {
        getStats(){ // Basic function for getting stats
            let self = this;
            axios.post(url, [
                {
                    "method":"race.getActive",
                    "params":[]
                },
                {
                    "method":"race.getLastFive",
                    "params":[]
                },
                {
                    "method":"horse.bestTimeEver",
                    "params":[]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    self.races = response[0];
                    self.finishedRaces = response[1];
                    self.bestTimeEver = response[2];
                }
            }).catch(function (error) {
                console.log(error);
            })
        },
        createRace () { // Create New Race
            let self = this;
            axios.post(url, [
                {
                    "method":"race.create",
                    "params":[]
                },
                {
                    "method":"race.getActive",
                    "params":[]
                },
                {
                    "method":"race.getLastFive",
                    "params":[]
                },
                {
                    "method":"horse.bestTimeEver",
                    "params":[]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response[0].success){
                    if(response !== false) {
                        self.races = response[1];
                        self.finishedRaces = response[2];
                        self.bestTimeEver = response[3];
                    }
                }else {
                    self.showErrors(response[0].errors);
                }
            }).catch(function (error) {
                console.log(error);
            })
        },
        progress () { // Add 10 seconds to all active races
            let self = this;
            axios.post(url, [
                {
                    "method":"race.progress",
                    "params":[]
                },
                {
                    "method":"race.getActive",
                    "params":[]
                },
                {
                    "method":"race.getLastFive",
                    "params":[]
                },
                {
                    "method":"horse.bestTimeEver",
                    "params":[]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    self.races = response[1];
                    self.finishedRaces = response[2];
                    self.bestTimeEver = response[3];
                }
            }).catch(function (error) {
                console.log(error);
            })
        },
        checkResponse(response) { // handle server response (JSON format)
            response = JSON.parse(response.request.response);
            console.log(response);
            if(response.success){
                return response.results
            } else {
                this.showErrors(response.errors);
                return false
            }
        },
        showErrors(errors) { // display errors
            if(errors.length > 0){
                this.hasErrors = true;
                this.errors = errors;
            }
        },
        indexOfHorse(arr, itm){
            for(let i = 0; i < arr.length; i++){
                if(arr[i].horseId === itm.horseId){
                    return i + 1;
                }
            }
            return 0;
        }
    }
});
