import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";
import Hotkeys from 'react-hot-keys';

class ToolbarActionsSplitComponent extends Component {

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
        let disabled = false;
        if (this.props.selection.count !== 1) {
            disabled = true;
        }
        return (<Hotkeys
                keyName="alt+s"
                onKeyDown={this.onSplitClick}>
                <button
                    disabled={disabled}
                    onClick={this.onSplitClick}>
                    Split
                </button>
            </Hotkeys>
        );
    }

    onSplitClick = () => {
        if(this.props.selection.count === 1){
            const type = this.props.selection.source.count > 0 ? 'source' : 'target';
            ProjectActions.openSegmentToSplit(getSegmentByOrder(this.props.selection[type].list[0], type));
            ProjectActions.addSegmentToSelection(-1);
            ProjectActions.onActionHover(null);
        }
    };

}

export default ToolbarActionsSplitComponent;
