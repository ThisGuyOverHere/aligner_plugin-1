let AppDispatcher = require('../Stores/AppDispatcher');
import ProjectsConstants  from '../Constants/Projects.constants';


let ProjectsActions = {

    getProjects: function () {
        AppDispatcher.dispatch({
            actionType: ProjectsConstants.GET_PROJECTS
        });
    },

};


export default ProjectsActions;