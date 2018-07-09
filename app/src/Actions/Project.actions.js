import ProjectStore from "../Stores/Project.store";

let AppDispatcher = require('../Stores/AppDispatcher');
import ProjectConstants from '../Constants/Project.constants';


let ProjectActions = {
    /**
     *
     * @param {Number} jobID The Job ID of current project
     */
    setJobID: function (jobID) {
        AppDispatcher.dispatch({
            actionType: ProjectConstants.SET_JOB_ID,
            jobID: jobID
        });
    },
    getRows: function () {
        const rows = [
            {
                order: 1000000000000,
                source: {
                    content: '1'
                },
                target: {
                    content: '1'
                },
                next: 2000000000000,
            },
            {
                order: 2000000000000,
                source: {
                    content: '2'
                },
                target: {
                    content: '3'
                },
                next: 3000000000000,
            },
            {
                order: 3000000000000,
                source: {
                    content: '3'
                },
                target: {
                    content: '4'
                },
                next: 4000000000000,
            },
            {
                order: 4000000000000,
                source: {
                    content: '4'
                },
                target: {
                    content: '5'
                },
                next: 5000000000000,
            },
            {
                order: 5000000000000,
                source: {
                    content: '5'
                },
                target: {
                    content: '2'
                },
                next: null,
            }
        ];

        AppDispatcher.dispatch({
            actionType: ProjectConstants.GET_ROWS,
            rows: rows
        });
    },
    /**
     *
     * @param {Object} log A log of move action from frontend
     * @param {String} log.type The type of segment: Source or Target
     * @param {Number} log.from The row's order of Drag action
     * @param {Number} log.to The row's order of Drop action
     */
    changeSegmentPosition: function (log) {
        let tmpRows = ProjectStore.project;

        let fromIndex = tmpRows.findIndex(i => i.get('order') === log.from);
        let toIndex = tmpRows.findIndex(i => i.get('order') === log.to);


        let mock = {
            order: tmpRows.getIn([toIndex, 'order']) + (tmpRows.getIn([toIndex + 1, 'order']) - tmpRows.getIn([toIndex, 'order'])) / 2,
            source: {
                content: null
            },
            target: {
                content: null
            },
            next: tmpRows.getIn([+toIndex + 1, 'order']),
        };
        mock[log.type].content = tmpRows.getIn([toIndex, log.type, 'content']);

        tmpRows = tmpRows.setIn([toIndex, log.type, 'content'], tmpRows.getIn([fromIndex, log.type, 'content']));
        tmpRows = tmpRows.setIn([fromIndex, log.type, 'content'], null);
        tmpRows = tmpRows.setIn([toIndex, 'next'], mock.order);
        const changes = [
            {
                action: 'update',
                rif_order: tmpRows.getIn([toIndex, 'order']),
                data: tmpRows.get(toIndex).toJS()
            },
            {
                action: 'create',
                rif_order: tmpRows.getIn([toIndex+1, 'order']),
                data: mock
            },
            {
                action: 'update',
                rif_order: tmpRows.getIn([fromIndex, 'order']),
                data: tmpRows.get(fromIndex).toJS()
            }
        ];
        console.log('Changes: ',changes);

        AppDispatcher.dispatch({
            actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
            rows: changes
        });
    }
};


export default ProjectActions;