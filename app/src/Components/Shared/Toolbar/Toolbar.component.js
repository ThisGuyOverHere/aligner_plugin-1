import React, {Component} from 'react';
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import ProjectActions from "../../../Actions/Project.actions";
import ToolbarSelectionComponent from "./ToolbarSelection/ToolbarSelection.component";
import ToolbarActionsComponent from "./ToolbarActions/ToolbarActions.component";
import ToolbarContextualNavigationComponent from "./ToolbarContextualNavigation/ToolbarContextualNavigation.component";

class ToolbarComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            selection: {
                source: {},
                target: {},
                count: 0
            },
        };
    }


    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
    }

    render() {
        return (
            <div id="toolbar">
                <div>
                    {!!this.state.selection.count && <ToolbarSelectionComponent selection={this.state.selection}/>}
                </div>
                <div>
                    {!!this.state.selection.count && <ToolbarActionsComponent/> }
                </div>
                <div>
                    <ToolbarContextualNavigationComponent/>
                </div>
            </div>
        );
    }


    storeSelection = (selection) => {
        this.setState({
            selection: selection
        })
    };
}

export default ToolbarComponent;