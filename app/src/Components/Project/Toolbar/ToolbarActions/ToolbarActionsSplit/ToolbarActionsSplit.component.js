import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";
import {Popup} from "semantic-ui-react";

class ToolbarActionsSplitComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {};
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of split action
        let disabled = false;
        let splitClasses = ['icon', 'arrows','alternate','horizontal'];
        if (this.props.selection.count > 1) {
            disabled = true;
        }
        const comp = <button
            disabled={disabled}
            onClick={this.onSplitClick}>
            <i className={splitClasses.join(" ")}></i>
            split
        </button>;
        return (
            <Popup trigger={comp} content='shortcut alt+R' on='hover' inverted />

        );
    }

    onSplitClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        ProjectActions.openSegmentToSplit(getSegmentByOrder(this.props.selection[type].list[0],type));
        ProjectActions.addSegmentToSelection(-1);
    };

}

export default ToolbarActionsSplitComponent;