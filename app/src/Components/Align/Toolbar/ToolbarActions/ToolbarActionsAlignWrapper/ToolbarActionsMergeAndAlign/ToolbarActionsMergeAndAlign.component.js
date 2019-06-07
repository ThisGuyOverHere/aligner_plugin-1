import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../../Actions/Project.actions";
import Hotkeys from "react-hot-keys";

class ToolbarActionsMergeAndAlignComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of split action
        let disabled = true;
        if (this.props.selection.count > 2
            && this.props.selection.source.count > 0
            && this.props.selection.target.count > 0) {
            disabled = false;
        }
        return <Hotkeys
            keyName="alt+a"
            onKeyDown={this.onClick}>
            <button
                disabled={disabled}
                onClick={this.onClick}>
                Merge and Align
            </button>
        </Hotkeys>;
    }

    onClick = () => {
        if (this.props.selection.count > 2
            && this.props.selection.source.count > 0
            && this.props.selection.target.count > 0) {
            ProjectActions.mergeAndAlignSegments(this.props.selection);
            ProjectActions.addSegmentToSelection(-1);
            ProjectActions.onActionHover(null);
            ProjectActions.onActionHover(null);
        }
    };
}

export default ToolbarActionsMergeAndAlignComponent;
