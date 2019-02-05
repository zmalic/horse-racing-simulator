Vue.use(VueMaterial.default, window.vuelidate.default, axios);
const { required, email, minLength, maxLength, minValue } = window.validators;
const { validationMixin  } = window.vuelidate;
const url = 'app.php';
app = new Vue({
    el: '#app',
    mixins: [validationMixin],
    data: () => ({
        errors: [],
        form: {
            id: 0,
            title: null,
            description: null,
            dueDate: null,
            completedAt: null,
            priority: 0,
            done: false
        },
        newTask: false,
        activeDialog: false,
        taskSaved: false,
        hasErrors: false,
        sending: false,
        orderBy: 'createdAt',
        orderType: 'desc',
        search: '',
        oldTaskCount:0,
        undoCount: 0,
        priorities: [
            {'id':0, 'title': 'Low'},
            {'id':1, 'title': 'Medium'},
            {'id':2, 'title': 'High'}
        ],
        tasks: []
    }),
    validations: {
        form: {
            title: {
                required,
                minLength: minLength(3),
                maxLength: maxLength(20)
            },
            description: {
                minLength: maxLength(300)
            },
            dueDate: {
                required
            }
        }
    },mounted:function(){
        this.searchTasks()
    },
    watch: {
        search: function () {
            this.debouncedGetAnswer()
        },
        orderBy: function () {
            this.searchTasks()
        },
        orderType: function () {
            this.searchTasks()
        }
    },
    created: function () {
        this.debouncedGetAnswer = _.debounce(this.searchTasks, 500) // wait half a second after typing
    },
    methods: {
        getValidationClass (fieldName) {
            const field = this.$v.form[fieldName];

            if (field) {
                return {
                    'md-invalid': field.$invalid && field.$dirty
                }
            }
        },
        getPriorityClass (id) { // set priority-1, priority-2 or priority-3 class depending on property value
            var priority = id >= 0 ? id : this.form.priority;
            return "priority priority-" + priority
        },
        clearForm () { // reset new task/edit task form
            this.$v.$reset();
            this.form.id = 0;
            this.form.title = null;
            this.form.description = null;
            this.form.dueDate = null;
            this.form.completedAt = null;
            this.form.priority = 0;
            this.form.done = false;
        },
        createNewTask() { // swich to new task and clear form
            this.clearForm();
            this.newTask = true
        },
        cancel() { // on cancel creating or editing task
            this.clearForm();
            this.newTask = false;

            let self = this;
            setTimeout(function(){
                window.scrollingTable = null;
                self.setTableListener()
            }, 500);
        },
        editTask(task) {
            this.form = this.clone(task);
            this.newTask = true
        },
        deleteTask(task) {
            this.form = this.clone(task);
            this.activeDialog = true
        },

        clone(obj) { // helper for cloning objects
            if (null == obj || "object" != typeof obj) return obj;
            let copy = obj.constructor();
            for (let attr in obj) {
                if (obj.hasOwnProperty(attr)) copy[attr] = obj[attr]
            }
            return copy
        },
        validateForm () { // if validation passed, call saving function
            this.$v.$touch();
            // console.log(this.$v.form.title.required);

            if (!this.$v.$invalid) {
                this.saveTask()
            }
        },
        frontEndDateTimeFormat(date) {
            return moment(date).format('DD.MM.YYYY HH:mm')
        },
        frontEndDateFormat(date) {
            return moment(date).format('DD.MM.YYYY')
        },
        backEndDateFormat(date) {
            return moment(date).format('YYYY-MM-DD')
        },
        checkResponse(response) { // handle server response (JSON format)
            response = JSON.parse(response.request.response);
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
        searchTasks(){ // Basic function for getting tasks
            this.sending = true;
            this.oldTaskCount = 0;
            let self = this;
            axios.post(url, [
                {
                    "method":"tasks.search",
                    "params":[this.search, this.orderBy, this.orderType, 0]
                },
                { // in same request get count of deletions for undo functionality
                    "method":"tasks.undoCount",
                    "params":[]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    self.tasks = response[0];
                    self.undoCount = response[1];
                    self.setTableListener()
                }
                self.sending = false
            }).catch(function (error) {
                self.sending = false
            })
        },
        setTableListener(){ // set listener for scrolling to bottom of task table
            if(window.scrollingTable == null) {
                let tables = document.getElementsByClassName("md-table-content");
                if(tables.length > 0) {
                    window.scrollingTable = tables[0];
                    window.scrollingTable.addEventListener('scroll', this.handleScroll);
                }
            }
        },
        handleScroll(){ // on scroll - load more if needed
            if(!this.sending && this.tasks.length > this.oldTaskCount &&
                window.scrollingTable.scrollHeight - window.scrollingTable.offsetHeight - window.scrollingTable.scrollTop < 100) {
                this.sending = true;
                let self = this;
                axios.post(url, [
                    {
                        "method":"tasks.search",
                        "params":[this.search, this.orderBy, this.orderType, this.tasks.length, 5]
                    },
                    {
                        "method":"tasks.undoCount",
                        "params":[]
                    }
                ]).then(function (response) {
                    response = self.checkResponse(response);
                    if(response !== false) {
                        self.oldTaskCount = self.tasks.length;
                        response[0].forEach(function(element) {
                            self.tasks.push(element);
                        });
                        self.undoCount = response[1];
                    }
                    setTimeout(function(){
                        self.sending = false;
                    },500);
                }).catch(function (error) {
                    self.sending = false
                })
            }
        },
        saveTask () { // save edited or new task
            this.sending = true;
            let task = this.clone(this.form);
            task.dueDate = this.backEndDateFormat(task.dueDate);
            let self = this;
            axios.post(url, [
                {
                    "method":"tasks.save",
                    "params":[task]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    response = response[0];
                    if(response.success){
                        if(task.id == 0) {
                            self.orderBy = 'createdAt';
                            self.orderType = 'desc';
                            self.search = '';
                        }
                        self.cancel();
                        self.taskSaved = true;
                        self.searchTasks();
                    } else {
                        self.showErrors(response.errors)
                    }
                }
                self.sending = false
            }).catch(function (error) {
                console.log(error);
                self.sending = false
            })
        },
        onDeleteConfirm () { // delete task
            this.sending = true;
            let self = this;
            axios.post(url, [
                {
                    "method":"tasks.delete",
                    "params":[self.form.id]
                },
                {
                    "method":"tasks.search",
                    "params":[this.search, this.orderBy, this.orderType, 0, this.tasks.length]
                },
                {
                    "method":"tasks.undoCount",
                    "params":[]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    if(response[0] == 1) { // deleted
                        self.tasks = response[1];
                        self.undoCount = response[2];
                    } else {
                        self.showErrors(response.errors)
                    }
                }
                self.sending = false
            }).catch(function (error) {
                console.log(error);
                self.sending = false
            })
        },
        changeStatus(item) { // change done status
            this.sending = true;
            let self = this;
            axios.post(url, [
                {
                    "method":"tasks.status",
                    "params":[item.id, !item.done]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    if(response[0] == 1) { // changed
                        item.done = !item.done;
                        if(item.done) {
                            item.completedAt = Date.now();
                        }
                    } else {
                        self.showErrors(response.errors)
                    }
                }
                self.sending = false
            }).catch(function (error) {
                console.log(error);
                self.sending = false
            })
        },
        undoDelete() { // undo delete
            this.sending = true;
            let self = this;
            axios.post(url, [
                {
                    "method":"tasks.undo",
                    "params":[]
                },
                {
                    "method":"tasks.search",
                    "params":[this.search, this.orderBy, this.orderType, 0, this.tasks.length]
                },
                {
                    "method":"tasks.undoCount",
                    "params":[]
                }
            ]).then(function (response) {
                response = self.checkResponse(response);
                if(response !== false) {
                    if(response[0] == 1) { // success
                        self.tasks = response[1];
                        self.undoCount = response[2];
                    } else {
                        self.showErrors(response.errors)
                    }
                }
                self.sending = false
            }).catch(function (error) {
                console.log(error);
                self.sending = false
            })
        }
    }
});
