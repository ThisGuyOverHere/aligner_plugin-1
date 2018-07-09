/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import ProjectConstants from '../Constants/Project.constants';
import assign from 'object-assign';
import { List, Set } from 'immutable';


EventEmitter.prototype.setMaxListeners(0);

let ProjectStore = assign({}, EventEmitter.prototype, {

    project: List(),
    
    updateAll: function (volumeAnalysis, project) {

    },
    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    },

    storeRows: function (rows) {
        this.project = this.project.push(...rows);
    },
    /*

     */
    storeRowsMovement: function (rows) {
        rows.map(row=>{
            const index = this.project.indexOf('order',row.rif_order);
            switch (row.action){
                case 'delete':
                    this.project = this.project.delete(index);
                    break;
                case 'create':
                    this.project = this.project.insert(index,row.data);
                    break;
                case 'update':
                    this.project = this.project.set(index,row.data);
                    break;
            }
        })
    }

});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case ProjectConstants.GET_ROWS:
            ProjectStore.storeRows(action.rows);
            ProjectStore.emitChange(ProjectConstants.RENDER_ROWS, ProjectStore.project.toJS());
            break;
    }
});


export default ProjectStore;


