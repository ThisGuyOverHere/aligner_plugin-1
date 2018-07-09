let AppDispatcher = require('../Stores/AppDispatcher');
import ProjectConstants from '../Constants/Project.constants';


let ProjectsActions = {

    getRows: function () {
        const rows = [{source: 1, target: 1},
            {source: 2, target: 4},
            {source: 3, target: 3},
            {source: 4, target: 5},
            {source: 5, target: 2},
            {source: 6, target: null}];

        AppDispatcher.dispatch({
            actionType: ProjectConstants.GET_ROWS,
            rows: rows
        });
    },
};


export default ProjectsActions;