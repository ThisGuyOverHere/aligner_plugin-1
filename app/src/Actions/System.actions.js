import SystemConstants from "../Constants/System.constants";
import {httpLogin, httpMe} from "../HttpRequests/System.http";
import {httpGetSegments} from "../HttpRequests/Alignment.http";
import ProjectConstants from "../Constants/Project.constants";

let AppDispatcher = require('../Stores/AppDispatcher');

let SystemActions = {
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

    /**
     *
     * @param {Boolean} status The status of Export Modal, true for open the modal and false for close
     */
    setExportModalStatus: function (status) {
        AppDispatcher.dispatch({
            actionType: SystemConstants.OPEN_EXPORT_MODAL,
            status: status
        });
    },

    /**
     *
     * @param {Boolean} status The status of Reset Password Modal, true for open the modal and false for close
     */
    setResetPasswordStatus: function (status) {
        AppDispatcher.dispatch({
            actionType: SystemConstants.OPEN_RESET_PASSWORD_MODAL,
            status: status
        });
    },

    /**
     *
     */
    checkUserStatus: function () {
        httpMe()
            .then(response => {
                AppDispatcher.dispatch({
                    actionType: SystemConstants.USER_STATUS,
                    status: response.data.user
                })
            })
            .catch( error => {
                AppDispatcher.dispatch({
                    actionType: SystemConstants.USER_STATUS,
                    status: false
                })
            })
    },

    /**
     *
     * @param data , an object with email and password
     */
    login: function (data) {
        httpLogin(data)
            .then(response => {
                AppDispatcher.dispatch({
                    actionType: SystemConstants.USER_STATUS,
                    status: response.data.user
                })
            })
            .catch( error => {
                AppDispatcher.dispatch({
                    actionType: SystemConstants.USER_STATUS,
                    status: false
                })
            })
    }

};


export default SystemActions;