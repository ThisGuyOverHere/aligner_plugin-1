/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import ProjectsConstants from '../Constants/Projects.constants';
import assign from 'object-assign';
import { Map } from 'immutable';


EventEmitter.prototype.setMaxListeners(0);

let ProjectsStore = assign({}, EventEmitter.prototype, {

    projects: null,

    updateAll: function (volumeAnalysis, project) {

    },
    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    }

});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case ProjectsConstants.GET_PROJECTS:
            ProjectsStore.emitChange(action.actionType);
            break;
    }
});


export default ProjectsStore;


