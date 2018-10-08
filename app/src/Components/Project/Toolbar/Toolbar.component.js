import React, {Component} from 'react';
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import ToolbarSelectionComponent from "./ToolbarSelection/ToolbarSelection.component";
import ToolbarActionsComponent from "./ToolbarActions/ToolbarActions.component";
import ToolbarContextualNavigationComponent from "./ToolbarContextualNavigation/ToolbarContextualNavigation.component";
import PropTypes from "prop-types";
import ToolbarRightHintComponent from "./ToolbarRightHint/ToolbarRightHint.component";

class ToolbarComponent extends Component {
    static propTypes = {
        jobConf: PropTypes.shape({
            password: PropTypes.string,
            id: PropTypes.any
        })
    };
    constructor(props) {
        super(props);
        this.state = {
            hintOpened: false,
            selection: {
                source: {
                    count: 0,
                    list: [],
                    map: {}
                },
                target: {
                    count: 0,
                    list: [],
                    map: {}
                },
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
                    <ToolbarActionsComponent selection={this.state.selection} jobConf={this.props.jobConf}/>
                </div>
                <div>
                    {/* <ToolbarContextualNavigationComponent/> */ }
                    <i className=" hint icon question circle outline" onClick={ this.hintModalOpened }></i>
                </div>
                { this.state.hintOpened && <ToolbarRightHintComponent close={this.hintModalOpened}/>}
            </div>
        );
    }


    storeSelection = (selection) => {
        this.setState({
            selection: selection
        })
    };

    hintModalOpened = () => {
        this.setState({
            hintOpened: !this.state.hintOpened,
        })
    };
}

export default ToolbarComponent;