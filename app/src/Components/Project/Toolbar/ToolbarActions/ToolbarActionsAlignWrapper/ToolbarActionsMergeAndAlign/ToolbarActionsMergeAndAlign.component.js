import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Popup} from "semantic-ui-react";

class ToolbarActionsMergeAndAlignComponent extends Component {

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
        let disabled = true;
        let classes = ['icon', 'check'];
        if (this.props.selection.count > 2
            && this.props.selection.source.count > 0
            && this.props.selection.target.count > 0) {
            disabled = false;
        }
        const comp = <button
            disabled={disabled}
            onClick={this.onClick}>
            <i className={classes.join(" ")}></i>
            Merge and Align
        </button>;
        return (
            <Popup trigger={comp} content='shortcut alt+A' on='hover' inverted/>
        );
    }

    onClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
    };

}

export default ToolbarActionsMergeAndAlignComponent;