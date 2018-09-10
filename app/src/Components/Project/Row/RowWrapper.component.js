import React, {Component} from 'react';
import PropTypes from "prop-types";
import RowComponent from "./Row.component";
import ActionsBetweenLines from "./ActionsBetweenLines/ActionsBetweenLines.component";

class RowWrapperComponent extends Component {

    static propTypes = {
        index: PropTypes.number.isRequired,
        animate: PropTypes.bool,
        mergeStatus: PropTypes.bool.isRequired,
        setAnimatedRow: PropTypes.func,
        scrollY: PropTypes.any,
        enableDrag: PropTypes.bool,
        rec: PropTypes.any,
        row: PropTypes.object.isRequired,
        selection: PropTypes.object
    };

    constructor(props) {
        super(props);

    }


    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {

    }

    componentDidMount() {

    }

    componentWillUnmount() {
    }

    render() {

        return <div className="row-wrapper">
            <ActionsBetweenLines row={this.props.row}/>
            <RowComponent {...this.props} />
        </div>
    }

}

export default RowWrapperComponent;