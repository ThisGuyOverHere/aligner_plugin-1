/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import assign from 'object-assign';
import TmxConstants from "../Constants/Tmx.constants";


EventEmitter.prototype.setMaxListeners(0);

let TmxStore = assign({}, EventEmitter.prototype, {

    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    },
});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case TmxConstants.GET_LIST:
            SystemStore.emitChange(TmxConstants.GET_LIST, action.list);
            break;
    }
});


export default TmxStore;


