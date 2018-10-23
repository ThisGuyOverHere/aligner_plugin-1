import SystemConstants from "../Constants/System.constants";

let AppDispatcher = require('../Stores/AppDispatcher');

let TmxActions = {
    /**
     *
     * @param {Boolean} status The status of login, true for open the login and false for close
     */
    setLoginStatus: function (status) {
        AppDispatcher.dispatch({
            actionType: SystemConstants.OPEN_LOGIN,
            status: status
        });
    },

};


export default TmxActions;
